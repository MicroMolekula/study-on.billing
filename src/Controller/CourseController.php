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
