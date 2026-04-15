<?php

namespace App\Enums;

enum NpcType: string
{
    case Enemy = 'enemy';
    case Ally = 'ally';
    case Neutral = 'neutral';

    public function label(): string
    {
        return match ($this) {
            self::Enemy => 'Enemy',
            self::Ally => 'Ally',
            self::Neutral => 'Neutral',
        };
    }
}
