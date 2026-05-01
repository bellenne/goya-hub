<?php

namespace App\Jobs;

use App\Models\GameSession;
use App\Services\Sessions\TrackGmSessionPresence;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DetectGmSessionPresenceLost implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $sessionId) {}

    public function handle(TrackGmSessionPresence $presence): void
    {
        $session = GameSession::query()->find($this->sessionId);

        if ($session === null) {
            return;
        }

        $presence->startGraceIfNoLiveGm($session);
    }
}
