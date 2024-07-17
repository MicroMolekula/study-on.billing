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

#[Route('/api/v1/transactions')]
class TransactionController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
    ) {
    }

    #[Route('/', name: 'app_transaction', methods:['GET'])]
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
