<?php

namespace App\Http\Controllers;

use App\Enums\GameRole;
use App\Http\Requests\UpdateGameMemberRoleRequest;
use App\Models\Game;
use App\Models\GameMember;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GameMemberController extends Controller
{
    public function updateRole(UpdateGameMemberRoleRequest $request, Game $game, GameMember $member): RedirectResponse
    {
        abort_unless($member->game_id === $game->id, 404);
        Gate::authorize('manageMemberRoles', $game);

        abort_if($member->user_id === $game->owner_id, 403);

        $member->update([
            'role' => GameRole::from($request->validated()['role']),
        ]);

        return redirect()
            ->route('games.show', $game)
            ->with('success', 'Member role updated.');
    }
}
