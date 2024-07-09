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

    #[Route('/api/v1/register', methods: ['POST'])]
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
