<?php

namespace App\Enums;

enum DiceType: string
{
    case D4 = 'd4';
    case D6 = 'd6';
    case D8 = 'd8';
    case D10 = 'd10';
    case D12 = 'd12';
    case D20 = 'd20';

    public function sides(): int
    {
        return match ($this) {
            self::D4 => 4,
            self::D6 => 6,
            self::D8 => 8,
            self::D10 => 10,
            self::D12 => 12,
            self::D20 => 20,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
