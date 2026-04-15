<?php

namespace App\Events;

use App\Models\SessionDiceRoll;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionDiceRolled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SessionDiceRoll $roll) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("session.rolls.{$this->roll->game_session_id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.dice.rolled';
    }

    public function broadcastWith(): array
    {
        return [
            'session_id' => $this->roll->game_session_id,
            'roll_id' => $this->roll->id,
        ];
    }
}
