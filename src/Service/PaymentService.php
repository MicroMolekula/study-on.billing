<?php

namespace App\Service;

use App\Config\CourseType;
use App\Config\TransactionType;
use App\Entity\Course;
use App\Entity\Transaction;
use App\Entity\User;
use App\Exception\DepositException;
use App\Exception\PaymentException;
use Doctrine\ORM\EntityManagerInterface;

class PaymentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderNotificationsService $orderNotificationsService,
        private int $rentalPeriod,
        private int $startingBalance,
    ) {
    }

    public function deposit(User $user, float $value = -1.0): Transaction
    {
        if($value === -1.0) {
            $value = $this->startingBalance;
        }

        $this->entityManager->getConnection()->beginTransaction();
        try {
            $transaction = new Transaction();
            $transaction->setType(TransactionType::DEPOSIT)
                ->setBillingUser($user)
                ->setCreatedAt(new \DateTimeImmutable())
                ->setValue($value);
            $this->entityManager->persist($transaction);

            $user->setBalance($user->getBalance() + $value);
            $this->entityManager->persist($user);

            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            return $transaction;
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new DepositException(message: 'При пополнение счета произошла ошибка');
        }
    }

    public function payment(User $user, Course $course): Transaction
    {
        $error = 'При покупке курса произошла ошибка';
        $this->entityManager->getConnection()->beginTransaction();
        try {
            if ($user->getBalance() <= $course->getPrice()) {
                $error = 'Не достаточно средств на счету для покупки курса';
                throw new \Exception(code: 1);
            }

            $date = new \DateTimeImmutable();
            
            $transaction = new Transaction();
            $transaction->setType(TransactionType::PAYMENT)
                ->setBillingUser($user)
                ->setCourse($course)
                ->setCreatedAt($date)
                ->setValue($course->getPrice());
            
            if ($course->getType() === CourseType::RENT) {
                $transaction->setExpiresAt($date->modify("+$this->rentalPeriod day"));
            }

            $this->entityManager->persist($transaction);
            
            $user->setBalance($user->getBalance() - $course->getPrice());
            $this->entityManager->persist($user);

            $this->entityManager->flush();     
            $this->entityManager->getConnection()->commit();

            $this->orderNotificationsService->sendNotify($user, $transaction);
            return $transaction;
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw new PaymentException(message: $error, code: $e->getCode());
        }
    }
}