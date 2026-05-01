<?php

namespace App\Services\Sessions;

use App\Enums\GameRole;
use App\Enums\SessionStatus;
use App\Events\SessionLobbyUpdated;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class JoinGameSession
{
    public function handle(GameSession $session, User $user, bool $allowAutoJoinGame = false): void
    {
        // MVP policy: joining during GM grace is allowed because the session still exists;
        // ended sessions are immutable history and cannot be joined again.
        abort_if($session->status === SessionStatus::Ended, Response::HTTP_CONFLICT, 'Session has ended.');

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
