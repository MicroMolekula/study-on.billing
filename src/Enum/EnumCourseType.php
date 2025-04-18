<?php

namespace App\Enum;

enum EnumCourseType: string
{
    case RENT = 'rent';
    case FREE = 'free';
    case BUY = 'buy';

    public function code(): int
    {
        return match ($this) {
            self::RENT => 1,
            self::FREE => 0,
            self::BUY => 2,
        };
    }

    public static function byString(string $value): EnumCourseType
    {
        return match ($value) {
            'rent' => self::RENT,
            'free' => self::FREE,
            'buy' => self::BUY,
        };
    }
}
