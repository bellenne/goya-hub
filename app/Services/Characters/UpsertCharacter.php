<?php

namespace App\Services\Characters;

use App\Models\Character;
use App\Models\Game;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UpsertCharacter
{
    public function handle(Game $game, User $user, array $attributes): Character
    {
        return DB::transaction(function () use ($game, $user, $attributes) {
            $character = Character::query()->firstOrNew([
                'game_id' => $game->id,
                'user_id' => $user->id,
            ]);

            $oldAvatarPath = $character->avatar_path;

            if (($attributes['avatar'] ?? null) instanceof UploadedFile) {
                $character->avatar_path = $attributes['avatar']->store('avatars', 'public');
            }

            $character->fill([
                'name' => $attributes['name'],
                'origin' => $attributes['origin'] ?? null,
                'notes' => $attributes['notes'] ?? null,
                'attribute_values' => $attributes['attribute_values'],
                'skill_values' => $attributes['skill_values'],
                'extra_field_values' => $attributes['extra_field_values'] ?? [],
                'is_active' => true,
            ]);

            $character->game()->associate($game);
            $character->user()->associate($user);
            $character->save();

            if (($attributes['avatar'] ?? null) instanceof UploadedFile && $oldAvatarPath && $oldAvatarPath !== $character->avatar_path) {
                Storage::disk('public')->delete($oldAvatarPath);
            }

            return $character;
        });
    }
}
