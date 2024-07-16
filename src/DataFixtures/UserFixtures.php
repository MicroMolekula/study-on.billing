<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

class UserFixtures extends Fixture
{
    private array $data = [
        [
            'email' => 'petrov@email.ru',
            'roles' => ['ROLE_USER'],
            'password' => 'qwer1234',
            'balance' => 6000,
        ],
        [
            'email' => 'krasikov@gmail.com',
            'roles' => ['ROLE_SUPER_ADMIN'],
            'password' => 'zxc12345',
            'balance' => 10000.47,
        ]
    ];

    public function __construct(
        private UserPasswordHasherInterface $hasher,
    ) {  
    }

    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $userData) {
            $user = new User();
            $user->setEmail($userData['email'])
                ->setRoles($userData['roles'])
                ->setBalance($userData['balance']);
            $hashedPassword = $this->hasher->hashPassword($user, $userData['password']);
            $user->setPassword($hashedPassword);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
