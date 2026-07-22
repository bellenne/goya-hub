<?php

namespace App\Http\Middleware;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'game_access' => function () use ($request): ?array {
                $game = $request->route('game');
                $user = $request->user();

                if (! $game instanceof Game || $user === null || ! Gate::forUser($user)->allows('view', $game)) {
                    return null;
                }

                return [
                    'id' => $game->id,
                    'can_manage_content' => Gate::forUser($user)->allows('manageContent', $game),
                    'can_view_sessions' => Gate::forUser($user)->allows('viewSessions', $game),
                    'can_manage_sessions' => Gate::forUser($user)->allows('manageSessions', $game),
                    'can_edit_character' => Gate::forUser($user)->allows('createCharacter', $game),
                    'can_view_characters' => Gate::forUser($user)->allows('viewCharacters', $game),
                    'can_view_tickets' => Gate::forUser($user)->allows('viewTickets', $game),
                    'current_user_character_id' => $game->characters()
                        ->where('user_id', $user->id)
                        ->value('id'),
                ];
            },
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'invite_link' => fn () => $request->session()->get('invite_link'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
