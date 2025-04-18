<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class IsExistsCourseException extends \Exception
{
    public $message = 'Курс с такми кодом уже существует';
}