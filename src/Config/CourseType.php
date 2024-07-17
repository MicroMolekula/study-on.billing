<?php 

namespace App\Config;

use App\Exception\TypeNotFoundException;

class CourseType
{
    public const FREE = 0;
    public const RENT = 1;
    public const BUY = 2;

    public static function typeToString(int $type): string
    {
        switch ($type) {
            case 0:
                return 'free';
            case 1:
                return 'rent';
            case 2:
                return 'buy';
            default:
                throw new TypeNotFoundException("Тип курса $type не найден");
        }
    }
}