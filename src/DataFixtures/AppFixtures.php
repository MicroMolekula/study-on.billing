<?php

namespace App\DataFixtures;

use App\Service\PaymentService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private PaymentService $paymentService,
    ) {  
    }

    public function load(ObjectManager $manager): void
    {
        (new UserFixtures($this->hasher, $this->paymentService))->load($manager);
        (new CourseFixtures())->load($manager);
        (new TransactionFixtures())->load($manager);
    }
}
