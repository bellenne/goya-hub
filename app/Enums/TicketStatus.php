<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingPlayer = 'waiting_player';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Открыт',
            self::InProgress => 'В работе',
            self::WaitingPlayer => 'Ожидает игрока',
            self::Resolved => 'Решён',
            self::Closed => 'Закрыт',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Open => 'sky',
            self::InProgress => 'amber',
            self::WaitingPlayer => 'violet',
            self::Resolved => 'emerald',
            self::Closed => 'stone',
        };
    }

    /**
     * @return array<int, array{value: string, label: string, tone: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => [
                'value' => $status->value,
                'label' => $status->label(),
                'tone' => $status->tone(),
            ],
            self::cases(),
        );
    }
}
