<?php

namespace App\Controller;

use App\Config\TransactionType;
use App\Dto\TransactionIndexFilterDto;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use OpenApi\Attributes as OA;

#[Route('/api/v1/transactions')]
class TransactionController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    #[Route('/', name: 'app_transaction', methods:['GET'])]
    #[OA\Get(
        path: '/api/v1/transactions/',
        summary: 'Возвращает данные о всех транзакциях пользователя',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Возвращает данные о всех транзакциях пользователя',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 13),
                            new OA\Property(property: 'created_at', type: 'datetime', example: '2024-07-13T13:46:07+00:00'),
                            new OA\Property(property: 'type', type: 'string', example: 'payment'),
                            new OA\Property(property: 'course_code', type: 'string', example: 'english-language'),
                            new OA\Property(property: 'amount', type: 'float', example: 1600.4),
                            new OA\Property(property: 'expires_at', type: 'datetime', example: '2024-08-13T14:01:37+00:00')
                        ]
                    )
                )
            )
        ]
    )]
    public function index(
        #[MapQueryString(
            validationFailedStatusCode: 400
        )] TransactionIndexFilterDto $filterDto = new TransactionIndexFilterDto(),
    ): JsonResponse
    {
        $user = $this->security->getUser();
        $transactions = $this->entityManager->getRepository(Transaction::class)
            ->findByUserWithFilter($user, $filterDto);

        $response = [];
        foreach ($transactions as $transaction) {
            $transactionJson = [
                'id' => $transaction->getId(),
                'created_at' => $transaction->getCreatedAt(),
                'type' => TransactionType::typeToString($transaction->getType()),
                'course_code' => $transaction->getCourse() ? $transaction->getCourse()->getCharsCode() : null,
                'amount' => $transaction->getValue(),
                'expires_at' => $transaction->getExpiresAt(),
            ];
            $response[] = array_filter($transactionJson, function ($var) {
                return $var !== null;
            });
        }

        return $this->json($response);
    }
}
