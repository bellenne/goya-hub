<?php

namespace App\Services\Sessions;

use App\Enums\GameRole;
use App\Events\SessionLobbyUpdated;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JoinGameSession
{
    public function handle(GameSession $session, User $user, bool $allowAutoJoinGame = false): void
    {
        DB::transaction(function () use ($session, $user, $allowAutoJoinGame) {
            $membership = $session->game->members()->where('user_id', $user->id)->first();

            if ($membership === null) {
                if (! $allowAutoJoinGame) {
                    abort(403);
                }

                $session->game->members()->create([
                    'user_id' => $user->id,
                    'role' => GameRole::Player,
                ]);
            }

            $session->participants()->updateOrCreate(
                ['user_id' => $user->id],
                ['joined_at' => now()],
            );
        });

        broadcast(new SessionLobbyUpdated($session->fresh(['game', 'participants.user'])))->toOthers();
    }
}
