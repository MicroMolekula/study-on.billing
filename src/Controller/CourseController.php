<?php

namespace App\Controller;

use App\Attribute\ValidateDeserialize;
use App\Config\CourseType;
use App\Dto\CourseCreateDto;
use App\Dto\CourseEditDto;
use App\Entity\Course;
use App\Entity\User;
use App\Enum\EnumCourseType;
use App\Exception\PaymentServiceException;
use App\Service\CourseService;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\SecurityBundle\Security;
use OpenApi\Attributes as OA;

#[Route('api/v1/courses')]
class CourseController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security               $security,
        private PaymentService         $paymentService,
        private CourseService          $courseService,
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
                        properties: [
                            new OA\Property(property: 'code', type: 'string', example: 'english-language'),
                            new OA\Property(property: 'type', type: 'string', example: 'rent'),
                            new OA\Property(property: 'price', type: 'float', example: 1000.50),
                        ],
                        type: 'object'
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
                    properties: [
                        new OA\Property(property: 'code', type: 'string', example: 'english-language'),
                        new OA\Property(property: 'type', type: 'string', example: 'rent'),
                        new OA\Property(property: 'price', type: 'float', example: 1000.50),
                    ],
                    type: 'object'
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

    #[Route('/', name: 'app_create_course', methods: ['POST'])]
    public function create(
        #[ValidateDeserialize]
        CourseCreateDto $course
    ): JsonResponse {
        $result = $this->courseService->create($course);
        if ($result) {
            return $this->json([
                'success' => true,
            ], Response::HTTP_CREATED);
        }
        return $this->json([
            'success' => false,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route('/{code}', name: 'app_edit_course', methods: ['POST'])]
    public function edit(
        string $code,
        #[ValidateDeserialize]
        CourseEditDto $course,
    ): JsonResponse {
        $result = $this->courseService->edit($code, $course);
        if ($result) {
            return $this->json([
                'success' => true,
            ], Response::HTTP_OK);
        }
        return $this->json([
           'success' => false,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
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
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Курс куплен'),
                        new OA\Property(property: 'course_code', type: 'string', example: 'math'),
                        new OA\Property(property: 'amount', type: 'float', example: 2000.43),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 406,
                description: 'Ошибка из-за недостатка средств на счету',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'code', type: 'integer', example: 406),
                        new OA\Property(property: 'message', type: 'string', example: 'На вашем счету не достаточно средств'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function pay(string $code): JsonResponse
    {
        $course = $this->entityManager->getRepository(Course::class)->findOneBy(['chars_code' => $code]);
        /** @var User $user */
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
                    'error_code' => 406,
                    'message' => 'На вашем счету не достаточно средств',
                ], 406);
            } else {
                return $this->json([
                    'error_code' => 500,
                    'message' => $exception->getMessage(),
                ], 500);
            }
        }
    }
}
