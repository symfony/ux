<?php

namespace App\Enum;

enum PizzaSize: int
{
    case Small = 12;
    case Medium = 14;
    case Large = 16;

    public function getReadable(): string
    {
        return match ($this) {
            self::Small => '12 inch',
            self::Medium => '14 inch',
            self::Large => '16 inch',
        };
    }
}
