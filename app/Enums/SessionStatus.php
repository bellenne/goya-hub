<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Lobby = 'lobby';
    case Active = 'active';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Lobby => 'Lobby',
            self::Active => 'Active',
            self::Finished => 'Finished',
        };
    }
}
