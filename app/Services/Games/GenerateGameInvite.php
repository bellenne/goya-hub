<?php

namespace App\Services\Games;

use App\Models\Game;
use App\Models\GameInvite;
use App\Models\User;
use Illuminate\Support\Str;

class GenerateGameInvite
{
    public function handle(Game $game, User $creator): GameInvite
    {
        $game->invites()->delete();

        return $game->invites()->create([
            'created_by_user_id' => $creator->id,
            'token' => Str::random(64),
        ]);
    }
}
