<?php

namespace App\Events;

use App\Models\Character;
use App\Models\GameSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCharacterInventoryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GameSession $session,
        public Character $character,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("session.inventory.{$this->session->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.inventory.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->session->id,
            'character_id' => $this->character->id,
        ];
    }
}
