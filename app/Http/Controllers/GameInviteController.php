<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\GameInvite;
use App\Services\Games\GenerateGameInvite;
use App\Services\Games\JoinGameByInvite;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GameInviteController extends Controller
{
    public function store(Game $game, GenerateGameInvite $generateGameInvite): RedirectResponse
    {
        Gate::authorize('createInvite', $game);

        $invite = $generateGameInvite->handle($game, auth()->user());

        return redirect()
            ->route('games.show', $game)
            ->with('success', 'Invite link generated.')
            ->with('invite_link', route('invites.show', $invite->token));
    }

    public function show(string $token): Response|RedirectResponse
    {
        $invite = GameInvite::query()
            ->with(['game.owner', 'game.members.user'])
            ->where('token', $token)
            ->firstOrFail();

        if (auth()->check() && $invite->game->members->contains('user_id', auth()->id())) {
            if (! $invite->game->characters()->where('user_id', auth()->id())->exists()) {
                return redirect()->route('games.character.edit', $invite->game)
                    ->with('success', 'You are already in this game. Create your character to get ready for sessions.');
            }

            return redirect()->route('games.show', $invite->game);
        }

        return Inertia::render('Invites/Show', [
            'invite' => [
                'token' => $invite->token,
                'is_expired' => $invite->isExpired(),
                'game' => [
                    'id' => $invite->game->id,
                    'name' => $invite->game->name,
                    'description' => $invite->game->description,
                    'owner' => $invite->game->owner->only(['id', 'name', 'email']),
                    'member_count' => $invite->game->members->count(),
                ],
            ],
        ]);
    }

    public function accept(string $token, JoinGameByInvite $joinGameByInvite): RedirectResponse
    {
        $invite = GameInvite::query()
            ->with('game')
            ->where('token', $token)
            ->firstOrFail();

        $joinGameByInvite->handle($invite, auth()->user());

        if (! $invite->game->characters()->where('user_id', auth()->id())->exists()) {
            return redirect()
                ->route('games.character.edit', $invite->game)
                ->with('success', 'You joined the game. Create your character before joining a session.');
        }

        return redirect()
            ->route('games.show', $invite->game)
            ->with('success', 'You joined the game.');
    }
}
