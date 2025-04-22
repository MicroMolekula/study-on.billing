<?php

namespace App\Dto;

use App\Validator\ExistsCourse;
use Symfony\Component\Validator\Constraints as Assert;

class TransactionIndexFilterDto
{
    #[Assert\Choice(['deposit', 'payment', ''], message: "Тип {{ value }} не существует")]
    public ?string $type = '';

    #[ExistsCourse]
    public ?string $course_code = '';

    public bool $skip_expired = false;
}