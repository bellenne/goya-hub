<?php

namespace App\Services\Sessions;

use App\Models\GameSession;
use App\Models\SessionSceneState;

class EnsureSessionSceneState
{
    public function handle(GameSession $session): SessionSceneState
    {
        return $session->sceneState()->firstOrCreate();
    }
}
