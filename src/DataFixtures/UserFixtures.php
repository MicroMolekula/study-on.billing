<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;
use App\Service\PaymentService;

class UserFixtures extends Fixture
{
    private array $data = [
        [
            'email' => 'petrov@email.ru',
            'roles' => ['ROLE_USER'],
            'password' => 'qwer1234',
        ],
        [
            'email' => 'krasikov@gmail.com',
            'roles' => ['ROLE_SUPER_ADMIN'],
            'password' => 'zxc12345',
        ]
    ];

    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private PaymentService $paymentService,
    ) {  
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $userData) {
            $user = new User();
            $user->setEmail($userData['email'])
                ->setRoles($userData['roles']);
            $hashedPassword = $this->hasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);
            $this->paymentService->deposit($user);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
