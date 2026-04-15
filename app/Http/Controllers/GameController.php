<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGameRequest;
use App\Models\Game;
use App\Services\Games\CreateGame;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GameController extends Controller
{
    public function index(): Response
    {
        $games = auth()->user()
            ->games()
            ->with(['owner', 'members.user'])
            ->latest('games.created_at')
            ->get()
            ->map(function (Game $game) {
                $membership = $game->members
                    ->firstWhere('user_id', auth()->id());

                return [
                    'id' => $game->id,
                    'name' => $game->name,
                    'description' => $game->description,
                    'owner' => $game->owner->only(['id', 'name', 'email']),
                    'member_count' => $game->members->count(),
                    'role' => $membership?->role->value,
                    'role_label' => $membership?->role->label(),
                    'created_at' => $game->created_at?->toISOString(),
                ];
            });

        return Inertia::render('Games/Index', [
            'games' => $games,
        ]);
    }

    public function store(StoreGameRequest $request, CreateGame $createGame): RedirectResponse
    {
        Gate::authorize('create', Game::class);

        $game = $createGame->handle($request->user(), $request->validated());

        return redirect()
            ->route('games.show', $game)
            ->with('success', 'Game created.');
    }

    public function show(Game $game): Response
    {
        Gate::authorize('view', $game);

        $game->load(['owner', 'members.user', 'invites']);

        $membership = $game->members->firstWhere('user_id', auth()->id());
        $activeInvite = $game->invites->sortByDesc('created_at')->first();

        return Inertia::render('Games/Show', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
                'description' => $game->description,
                'owner' => $game->owner->only(['id', 'name', 'email']),
                'current_user_role' => $membership?->role->value,
                'current_user_role_label' => $membership?->role->label(),
                'members' => $game->members
                    ->sortBy(fn ($member) => $member->created_at)
                    ->values()
                    ->map(fn ($member) => [
                        'id' => $member->id,
                        'role' => $member->role->value,
                        'role_label' => $member->role->label(),
                        'user' => $member->user->only(['id', 'name', 'email']),
                    ]),
                'invite_link' => $activeInvite
                    ? route('invites.show', $activeInvite->token)
                    : null,
                'can_manage_member_roles' => Gate::allows('manageMemberRoles', $game),
                'can_manage_invites' => Gate::allows('createInvite', $game),
                'can_manage_content' => Gate::allows('manageContent', $game),
                'can_view_sessions' => Gate::allows('viewSessions', $game),
                'can_manage_sessions' => Gate::allows('manageSessions', $game),
                'can_edit_character' => Gate::allows('createCharacter', $game),
                'can_view_characters' => Gate::allows('viewCharacters', $game),
                'current_user_character_id' => $game->characters()
                    ->where('user_id', auth()->id())
                    ->value('id'),
            ],
        ]);
    }
}
