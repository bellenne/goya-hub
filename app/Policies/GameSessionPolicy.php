<?php

namespace App\Policies;

use App\Models\GameSession;
use App\Models\User;

class GameSessionPolicy
{
    public function view(User $user, GameSession $session): bool
    {
        return $session->game->members()->where('user_id', $user->id)->exists();
    }

    public function join(User $user, GameSession $session): bool
    {
        return $session->game->members()->where('user_id', $user->id)->exists();
    }

    public function start(User $user, GameSession $session): bool
    {
        $membership = $session->game->members()->where('user_id', $user->id)->first();

        return $membership !== null && $membership->role->canManageInvites();
    }
}
