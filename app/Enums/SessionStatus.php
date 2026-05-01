<?php

namespace App\Enums;

enum SessionStatus: string
{
    case Lobby = 'lobby';
    case Active = 'active';
    case GmDisconnectedGrace = 'gm_disconnected_grace';
    case Ended = 'ended';

    public function label(): string
    {
        return match ($this) {
            self::Lobby => 'Lobby',
            self::Active => 'Active',
            self::GmDisconnectedGrace => 'GM disconnected',
            self::Ended => 'Ended',
        };
    }

    public function allowsJoining(): bool
    {
        return match ($this) {
            self::Lobby, self::Active, self::GmDisconnectedGrace => true,
            self::Ended => false,
        };
    }
}
