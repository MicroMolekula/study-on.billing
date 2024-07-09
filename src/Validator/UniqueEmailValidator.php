<?php

namespace App\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use App\Entity\User;

class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(
        private EntityManagerInterface $manager,
    ) {  
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var UniqueEmail $constraint */
        
        $user = $this->manager->getRepository(User::class)->findOneBy(['email' => $value]);

        if (null === $user) {
            return;
        }

        // TODO: implement the validation here
        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
