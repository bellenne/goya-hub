<?php

namespace App\Events;

use App\Models\GameSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionLifecycleUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public GameSession $session,
        public string $event,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("session.lobby.{$this->session->id}"),
            new PrivateChannel("session.scene.{$this->session->id}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.lifecycle.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'event' => $this->event,
            'session_id' => $this->session->id,
            'status' => $this->session->status->value,
            'status_label' => $this->session->status->label(),
            'gm_grace_started_at' => $this->session->gm_grace_started_at?->toISOString(),
            'gm_grace_ends_at' => $this->session->gm_grace_ends_at?->toISOString(),
            'ended_at' => $this->session->ended_at?->toISOString(),
        ];
    }
}
