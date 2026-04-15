<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

class GamePolicy
{
    protected function membershipFor(User $user, Game $game)
    {
        return $game->members()
            ->where('user_id', $user->id)
            ->first();
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Game $game): bool
    {
        return $game->members()->where('user_id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->exists;
    }

    public function createInvite(User $user, Game $game): bool
    {
        $membership = $this->membershipFor($user, $game);

        if ($membership === null) {
            return false;
        }

        return $membership->role->canManageInvites();
    }

    public function createCharacter(User $user, Game $game): bool
    {
        return $game->members()->where('user_id', $user->id)->exists();
    }

    public function viewCharacters(User $user, Game $game): bool
    {
        $membership = $this->membershipFor($user, $game);

        if ($membership === null) {
            return false;
        }

        return $membership->role->canManageInvites();
    }

    public function manageContent(User $user, Game $game): bool
    {
        $membership = $this->membershipFor($user, $game);

        if ($membership === null) {
            return false;
        }

        return $membership->role->canManageInvites();
    }

    public function viewSessions(User $user, Game $game): bool
    {
        return $game->members()->where('user_id', $user->id)->exists();
    }

    public function manageSessions(User $user, Game $game): bool
    {
        $membership = $this->membershipFor($user, $game);

        return $membership !== null && $membership->role->canManageInvites();
    }

    public function manageMemberRoles(User $user, Game $game): bool
    {
        return $game->owner_id === $user->id;
    }

    public function delete(User $user, Game $game): bool
    {
        return $game->owner_id === $user->id;
    }

    public function transferOwnership(User $user, Game $game): bool
    {
        return $game->owner_id === $user->id;
    }

    public function removeMember(User $user, Game $game): bool
    {
        return $game->owner_id === $user->id;
    }
}
