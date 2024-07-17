<?php

namespace App\Config;

use App\Exception\TypeNotFoundException;

class TransactionType
{
    public const DEPOSIT = 0;
    public const PAYMENT = 1;

    public static function typeToString(int $type): string
    {
        switch ($type) {
            case 0:
                return 'deposit';
            case 1:
                return 'payment';
            default:
                throw new TypeNotFoundException("Тип транзакции $type не найден");
        }
    }

    public static function stringToType(string $str): int
    {
        switch ($str) {
            case 'deposit':
                return 0;
            case 'payment':
                return 1;
            default:
                throw new TypeNotFoundException("Тип транзакции $str не существует");
        }
    }
}