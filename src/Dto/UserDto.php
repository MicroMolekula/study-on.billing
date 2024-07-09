<?php

namespace App\Dto;

use App\Validator\UniqueEmail;
use Symfony\Component\Validator\Constraints as Assert;

class UserDto
{
    #[Assert\NotBlank(message: "Поле email обязательно")]
    #[Assert\Email(message: "Некоректрный адрес электронной почты")]
    #[UniqueEmail]
    public string $username;

    #[Assert\NotBlank(message: "Поле пароль обязательно")]
    #[Assert\Length(min: 6,  minMessage: "Пароль должен содержать 6 символов или больше")]
    public string $password;
}