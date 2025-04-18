<?php

namespace App\Validator;

use App\Enum\EnumCourseType;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IsTypeCourseValidator extends ConstraintValidator
{
    private array $types = [
        EnumCourseType::BUY->value,
        EnumCourseType::FREE->value,
        EnumCourseType::RENT->value,
    ];

    public function validate(mixed $value, Constraint $constraint)
    {
        if ($value === null) {
            return;
        }

        if (in_array($value, $this->types, true)) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}