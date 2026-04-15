<?php

namespace App\Enums;

enum GameRole: string
{
    case Gm = 'gm';
    case CoGm = 'co_gm';
    case Player = 'player';

    public function label(): string
    {
        return match ($this) {
            self::Gm => 'GM',
            self::CoGm => 'Co-GM',
            self::Player => 'Player',
        };
    }

    public function canManageInvites(): bool
    {
        return match ($this) {
            self::Gm, self::CoGm => true,
            self::Player => false,
        };
    }
}
