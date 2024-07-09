<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class UniqueEmail extends Constraint
{
    public string $message = 'Пользователь с таким email уже сущесвует';
}
