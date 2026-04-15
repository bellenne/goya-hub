<?php

namespace App\Services\Games;

use App\Enums\GameRole;
use App\Models\GameInvite;
use App\Models\GameMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class JoinGameByInvite
{
    public function handle(GameInvite $invite, User $user): GameMember
    {
        if ($invite->isExpired()) {
            throw new RuntimeException('Invite link is expired.');
        }

        return DB::transaction(function () use ($invite, $user) {
            return $invite->game->members()->firstOrCreate(
                ['user_id' => $user->id],
                ['role' => GameRole::Player],
            );
        });
    }
}
