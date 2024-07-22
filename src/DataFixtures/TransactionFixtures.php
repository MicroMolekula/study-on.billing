<?php

namespace App\DataFixtures;

use App\Config\TransactionType;
use App\Entity\Transaction;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use App\Entity\Course;
use DateTimeImmutable;

class TransactionFixtures extends Fixture
{
    private array $data = [
        'petrov@email.ru' => [
            [
                'course' => null,
                'type' => TransactionType::DEPOSIT,
                'value' => 3000,
                'created_at' => '2024-07-13T13:46:07+00:00',
                'expires_at' => null,
            ],
            [
                'course' => 'english-language',
                'type' => TransactionType::PAYMENT, 
                'value' => 1000.50,
                'created_at' => '2024-07-13T14:01:37+00:00',
                'expires_at' => '2024-07-20T14:01:37+00:00',
            ],
            [
                'course' => 'physics',
                'type' => TransactionType::PAYMENT,
                'value' => 1900.20,
                'created_at' => '2024-07-14T11:01:20+00:00',
                'expires_at' => null,
            ],
            [
                'course' => null,
                'type' => TransactionType::DEPOSIT,
                'value' => 2099.30,
                'created_at' => '2024-07-14T11:01:20+00:00',
                'expires_at' => null,
            ],
        ],
        'krasikov@gmail.com' => [
            [
                'course' => null,
                'type' => TransactionType::DEPOSIT,
                'value' => 6000,
                'created_at' => '2024-07-15T12:46:07+00:00',
                'expires_at' => null,
            ],
            [
                'course' => 'math',
                'type' => TransactionType::PAYMENT,
                'value' => 2000.50,
                'created_at' => '2024-07-15T13:46:07+00:00',
                'expires_at' => null,
            ],
            [
                'course' => null,
                'type' => TransactionType::DEPOSIT,
                'value' => 5999.50,
                'created_at' => '2024-07-15T16:46:07+00:00',
                'expires_at' => null,
            ],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $userEmail => $dataTrans) {
            $user = $manager->getRepository(User::class)->findOneBy(['email' => $userEmail]);
            foreach ($dataTrans as $dataOneTrans) {
                $transaction = new Transaction();
                $course = $manager->getRepository(Course::class)->findOneBy(['chars_code' => $dataOneTrans['course']]);
                $transaction->setBillingUser($user)
                    ->setCourse($course)
                    ->setType($dataOneTrans['type'])
                    ->setValue($dataOneTrans['value'])
                    ->setCreatedAt(new DateTimeImmutable($dataOneTrans['created_at']))
                    ->setExpiresAt($dataOneTrans['expires_at'] ? new DateTimeImmutable($dataOneTrans['expires_at']) : null);
                $manager->persist($transaction);
            }
        }

        $manager->flush();
    }
}
