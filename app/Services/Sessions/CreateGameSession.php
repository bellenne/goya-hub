<?php

namespace App\Services\Sessions;

use App\Enums\SessionStatus;
use App\Models\Game;
use App\Models\GameSession;
use Illuminate\Support\Str;

class CreateGameSession
{
    public function handle(Game $game, array $attributes): GameSession
    {
        return $game->sessions()->create([
            'title' => $attributes['title'],
            'invite_code' => strtoupper(Str::random(6)),
            'invite_token' => Str::random(64),
            'status' => SessionStatus::Lobby,
            'started_at' => now(),
        ]);
    }
}
