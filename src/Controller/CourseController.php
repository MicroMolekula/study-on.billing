<?php

namespace App\Controller;

use App\Config\CourseType;
use App\Entity\Course;
use App\Exception\PaymentServiceException;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\SecurityBundle\Security;
use OpenApi\Attributes as OA;

#[Route('api/v1/courses')]
class CourseController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private PaymentService $paymentService,
    ) {  
    }

    #[Route('/', name: 'app_course', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/courses/',
        summary: 'Возвращает данные о всех курсах',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Возвращает данные о всех курсах',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'code', type: 'string', example: 'english-language'),
                            new OA\Property(property: 'type', type: 'string', example: 'rent'),
                            new OA\Property(property: 'price', type: 'float', example: 1000.50),
                        ]
                    )
                )
            )
        ]
    )]
    public function index(): JsonResponse
    {
        $courses = $this->entityManager->getRepository(Course::class)->findAll();

        $response = [];
        foreach ($courses as $course) {
            $courseJson = [
                'code' => $course->getCharsCode(),
                'type' => CourseType::typeToString($course->getType()),
                'price' => $course->getPrice(),
            ];
            $response[] = array_filter($courseJson, function($var) {
                return $var !== null;
            });
        }

        return $this->json($response);
    }

    #[Route('/{code}', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v1/courses/{code}',
        summary: 'Возвращает данные об одном  курсе',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Возвращает данные об одном  курсе',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'code', type: 'string', example: 'english-language'),
                        new OA\Property(property: 'type', type: 'string', example: 'rent'),
                        new OA\Property(property: 'price', type: 'float', example: 1000.50),
                    ]
                )
            )
        ]
    )]
    public function show(string $code): JsonResponse
    {
        $course = $this->entityManager->getRepository(Course::class)->findOneBy(['chars_code' => $code]);

        if (null === $course) {
            return $this->json([
                'error_code' => 404,
                'message' => 'Курс не найден',
            ], 404);
        }

        $response = [
            'code' => $course->getCharsCode(),
            'type' => CourseType::typeToString($course->getType()),
            'price' => $course->getPrice(),
        ];
        $response = array_filter($response, function($var) {
            return $var !== null;
        });

        return $this->json($response);
    }


    #[Route('/{code}/pay', methods: ['POST'])]
    #[OA\Post(
        path: "/api/v1/courses/{code}/pay",
        summary: "Оформляет покупку указанного курса",
        responses: [
            new OA\Response(
                response: 200,
                description: 'Оформляет покупку указанного курса',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Курс куплен'),
                        new OA\Property(property: 'course_code', type: 'string', example: 'math'),
                        new OA\Property(property: 'amount', type: 'float', example: 2000.43),
                    ]
                )
            ),
            new OA\Response(
                response: 406,
                description: 'Ошибка из-за недостатка средств на счету',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 406),
                        new OA\Property(property: 'message', type: 'string', example: 'На вашем счету не достаточно средств'),
                    ]
                )
            ),
        ]
    )]
    public function pay(string $code): JsonResponse
    {
        $course = $this->entityManager->getRepository(Course::class)->findOneBy(['chars_code' => $code]);
        $user = $this->security->getUser();

        if (null === $course) {
            return $this->json([
                'error_code' => 404,
                'message' => 'Курс не найден',
            ], 404);
        }

        try {
            $transaction = $this->paymentService->payment($user, $course);
            return $this->json([
                'message' => 'Курс куплен',
                'course_code' => $transaction->getCourse()->getCharsCode(),
                'amount' => $transaction->getValue(),
            ]);
        } catch (PaymentServiceException $exception) {
            if($exception->getCode() === 1) {
                return $this->json([
                    'code' => 406,
                    'message' => 'На вашем счету не достаточно средств',
                ], 406);
            } else {
                return $this->json([
                    'code' => 500,
                    'message' => $exception->getMessage(),
                ], 500);
            }
        }
    }
}
