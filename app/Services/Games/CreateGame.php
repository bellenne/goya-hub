<?php

namespace App\Services\Games;

use App\Enums\GameRole;
use App\Models\Game;
use App\Models\User;
use App\Services\Characters\CharacterTemplateFactory;
use Illuminate\Support\Facades\DB;

class CreateGame
{
    public function handle(User $owner, array $attributes): Game
    {
        return DB::transaction(function () use ($owner, $attributes) {
            $game = Game::query()->create([
                'owner_id' => $owner->id,
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'character_template' => CharacterTemplateFactory::default(),
            ]);

            $game->members()->create([
                'user_id' => $owner->id,
                'role' => GameRole::Gm,
            ]);

            return $game;
        });
    }
}
