<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Dto\UserDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use OpenApi\Attributes as OA;

class UserController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $hasher,
        private JWTTokenManagerInterface $jwtManager,
        private EntityManagerInterface $entityManager,
        private TokenStorageInterface $tokenStorageInterface,
    ) {
    }

    #[Route('/api/v1/auth', methods: ['POST'])]
    #[OA\Post(
        path: "/api/v1/auth",
        summary: "Авторизация в сервисе billing",
        requestBody: new OA\RequestBody(
            description: "Данные пользователя",
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'name@mail.ru'),
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                ] 
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Авторизует и возвращает jwt токен пользователя',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciO...')
                    ]
                )
            )
        ]
    )]
    public function auth(): JsonResponse
    {
        return $this->json([]);
    }

    #[Route('/api/v1/register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v1/register',
        summary: 'Выполняет регистрацию в сервисе billing',
        requestBody: new OA\RequestBody(
            description: "Данные пользователя",
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'name@mail.ru'),
                    new OA\Property(property: 'password', type: 'string', minLength: 6),
                ] 
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Регистрирует нового пользователя",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: 'eyJ0eXAiOiJKV1QiLCJhbGciO...'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_USER')),
                    ]
                )
            )
        ] 
    )]
    public function register(Request $request): JsonResponse
    {
        $serializer = SerializerBuilder::create()->build();
        $userDto = $serializer->deserialize($request->getContent(), UserDto::class, 'json');

        // Валидация
        $errors = $this->validator->validate($userDto);
        if (count($errors) > 0) {
            $errorMessage = [];
            foreach ($errors as $error) {
                $errorMessage[] = $error->getMessage();
            }
            return $this->json(['message' => implode(', ', $errorMessage)], 400);
        }

        $user = User::fromDto($userDto, $this->hasher);
        $token = $this->jwtManager->create($user);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'token' => $token,
            'roles' => $user->getRoles(),
        ], 201);
    }

    #[Route(path:'/api/v1/users/current', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/users/current',
        summary: 'Возвращает данные о текущем пользователе',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Вовращает данные о текущем пользователе',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'username', type: 'string', example: 'name@mail.com'),
                        new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string', example: 'ROLE_USER')),
                        new OA\Property(property: 'balance', type: 'float', example: 1000.50),
                    ]
                )
            )
        ]
    )]
    public function current(): JsonResponse
    {
        $token = $this->tokenStorageInterface->getToken();
        $decodedJwtToken = $this->jwtManager->decode($token);
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $decodedJwtToken['username']]);

        return $this->json([
            'username' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'balance' => $user->getBalance(),
        ]);
    }

}
