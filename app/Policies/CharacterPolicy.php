<?php

namespace App\Policies;

use App\Models\Character;
use App\Models\User;

class CharacterPolicy
{
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Character $character): bool
    {
        if ($character->user_id === $user->id) {
            return true;
        }

        $membership = $character->game->members()
            ->where('user_id', $user->id)
            ->first();

        return $membership !== null && $membership->role->canManageInvites();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Character $character): bool
    {
        return $character->user_id === $user->id;
    }

    public function manageInventory(User $user, Character $character): bool
    {
        $membership = $character->game->members()
            ->where('user_id', $user->id)
            ->first();

        return $membership !== null && $membership->role->canManageInvites();
    }
}
