<?php

namespace App\Tests\Controller;

use App\Config\CourseType;
use App\Config\TransactionType;
use App\DataFixtures\CourseFixtures;
use App\DataFixtures\TransactionFixtures;
use App\DataFixtures\UserFixtures;
use App\Dto\TransactionIndexFilterDto;
use App\Entity\Transaction;
use App\Entity\User;
use App\Tests\AbstractTest;


class TransactionControllerTest extends AbstractTest
{
    public function getFixtures(): array
    {
        return [
            UserFixtures::class,
            CourseFixtures::class,
            TransactionFixtures::class
        ];
    }

    public function assertTransactionsResponse(array $transactions, array $transactionsResponse): void
    {
        for ($i = 0; $i < count($transactions); $i++) {
            $this->assertEquals(
                $transactions[$i]->getId(),
                $transactionsResponse[$i]['id']
            );
            $this->assertEquals(
                $transactions[$i]->getCreatedAt(),
                new \DateTimeImmutable($transactionsResponse[$i]['created_at'])
            );
            $this->assertEquals(
                $transactions[$i]->getValue(),
                $transactionsResponse[$i]['amount']
            );
            $this->assertEquals(
                $transactions[$i]->getType(),
                TransactionType::stringToType($transactionsResponse[$i]['type'])
            );
            if ($transactions[$i]->getType() === TransactionType::PAYMENT) {
                $this->assertEquals(
                    $transactions[$i]->getCourse()->getCharsCode(),
                    $transactionsResponse[$i]['course_code']
                );
                if ($transactions[$i]->getCourse()->getType() === CourseType::RENT) {
                    $this->assertEquals(
                        $transactions[$i]->getExpiresAt(),
                        new \DateTimeImmutable($transactionsResponse[$i]['expires_at'])
                    );
                }
            }
        }
    }

    public function testTransactionIndex(): void
    {
        $client = static::getClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();      

        // Авторизация
        $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => 'petrov@email.ru',
            'password' => 'qwer1234',
        ]);
        $this->assertResponseIsSuccessful();
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'petrov@email.ru']);

        $token = json_decode($client->getResponse()->getContent(), true)['token'];

        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));
        $client->jsonRequest('GET', '/api/v1/transactions/');
        $this->assertResponseIsSuccessful();

        $transactions = $entityManager->getRepository(Transaction::class)->findByUserWithFilter($user, new TransactionIndexFilterDto());
        $transactionsResponse = json_decode($client->getResponse()->getContent(), true);

        $this->assertTransactionsResponse($transactions, $transactionsResponse);

        $client->jsonRequest('GET', '/api/v1/transactions/?type=deposit');
        $transactionsResponse = json_decode($client->getResponse()->getContent(), true);
        $filter = new TransactionIndexFilterDto();
        $filter->type = 'deposit';
        $transactions = $entityManager->getRepository(Transaction::class)->findByUserWithFilter($user, $filter);

        $this->assertTransactionsResponse($transactions, $transactionsResponse);

        $client->jsonRequest('GET', '/api/v1/transactions/?course_code=physics');
        $transactionsResponse = json_decode($client->getResponse()->getContent(), true);
        $filter = new TransactionIndexFilterDto();
        $filter->course_code = 'physics';
        $transactions = $entityManager->getRepository(Transaction::class)->findByUserWithFilter($user, $filter);

        $this->assertTransactionsResponse($transactions, $transactionsResponse);


        $client->jsonRequest('GET', '/api/v1/transactions/?skip_expired=true');
        $transactionsResponse = json_decode($client->getResponse()->getContent(), true);
        $filter = new TransactionIndexFilterDto();
        $filter->skip_expired = true;
        $transactions = $entityManager->getRepository(Transaction::class)->findByUserWithFilter($user, $filter);

        $this->assertTransactionsResponse($transactions, $transactionsResponse);
    }

    public function testTransactionsIndexFailed(): void
    {
        $client = static::getClient();
        $entityManager = static::getContainer()->get('doctrine')->getManager();

        // Авторизация
        $client->jsonRequest('POST', '/api/v1/auth', [
            'username' => 'petrov@email.ru',
            'password' => 'qwer1234',
        ]);
        $this->assertResponseIsSuccessful();
        $user = $entityManager->getRepository(User::class)->findOneBy(['email' => 'petrov@email.ru']);

        $token = json_decode($client->getResponse()->getContent(), true)['token'];
        $client->setServerParameter('HTTP_Authorization', sprintf('Bearer %s', $token));

        $client->jsonRequest('GET', '/api/v1/transactions/?type=buy');
        $this->assertResponseStatusCodeSame(400);

        $client->jsonRequest('GET', '/api/v1/transactions/?course_code=programming');
        $this->assertResponseStatusCodeSame(400);
    }
}
