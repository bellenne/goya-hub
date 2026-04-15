<?php

namespace App\Services\Sessions;

use App\Enums\SessionStatus;
use App\Events\SessionCharacterInventoryUpdated;
use App\Models\Character;

class BroadcastCharacterInventoryUpdate
{
    public function handle(Character $character): void
    {
        $character->loadMissing('game.sessions');

        $activeSessions = $character->game->sessions
            ->where('status', SessionStatus::Active)
            ->values();

        foreach ($activeSessions as $session) {
            broadcast(new SessionCharacterInventoryUpdated($session, $character))->toOthers();
        }
    }
}
