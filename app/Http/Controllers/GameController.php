<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Http\Requests\StoreGameRequest;
use App\Models\Character;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Item;
use App\Models\Npc;
use App\Models\Ticket;
use App\Services\Games\CreateGame;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
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
        $canManageContent = Gate::allows('manageContent', $game);
        $canViewTickets = Gate::allows('viewTickets', $game);
        $canManageTickets = Gate::allows('manageTickets', $game);

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
                'can_manage_content' => $canManageContent,
                'can_view_sessions' => Gate::allows('viewSessions', $game),
                'can_manage_sessions' => Gate::allows('manageSessions', $game),
                'can_edit_character' => Gate::allows('createCharacter', $game),
                'can_view_characters' => Gate::allows('viewCharacters', $game),
                'can_view_tickets' => $canViewTickets,
                'current_user_character_id' => $game->characters()
                    ->where('user_id', auth()->id())
                    ->value('id'),
                'dashboard' => $this->dashboardPayload($game, $canManageContent, $canViewTickets, $canManageTickets),
            ],
        ]);
    }

    protected function dashboardPayload(Game $game, bool $canManageContent, bool $canViewTickets, bool $canManageTickets): array
    {
        $characters = $game->characters()
            ->with('user:id,name,email')
            ->latest()
            ->get();
        $recentSessions = $game->sessions()
            ->withCount('participants')
            ->latest()
            ->limit(4)
            ->get();
        $activeSession = $recentSessions
            ->first(fn (GameSession $session) => $session->ended_at === null && $session->status->value !== 'ended')
            ?? $recentSessions->first();

        $tickets = collect();

        if ($canViewTickets) {
            $tickets = $game->tickets()
                ->with('creator:id,name,email')
                ->when(! $canManageTickets, fn ($query) => $query->where('creator_user_id', auth()->id()))
                ->whereNotIn('status', [TicketStatus::Closed->value, TicketStatus::Resolved->value])
                ->latest('last_message_at')
                ->latest('updated_at')
                ->limit(4)
                ->get();
        }

        $npcs = $canManageContent
            ? $game->npcs()->latest()->limit(4)->get()
            : collect();
        $featuredNpc = $npcs->first();
        $items = $canManageContent
            ? $game->items()->latest()->limit(5)->get()
            : collect();
        $background = $canManageContent
            ? $game->backgrounds()->latest()->first()
            : null;
        $selectedCharacter = $characters->firstWhere('is_active', true) ?? $characters->first();

        return [
            'summary' => [
                'characters' => $characters->count(),
                'active_characters' => $characters->where('is_active', true)->count(),
                'sessions' => $game->sessions()->count(),
                'active_tickets' => $canViewTickets
                    ? $game->tickets()
                        ->when(! $canManageTickets, fn ($query) => $query->where('creator_user_id', auth()->id()))
                        ->whereNotIn('status', [TicketStatus::Closed->value, TicketStatus::Resolved->value])
                        ->count()
                    : 0,
                'npcs' => $canManageContent ? $game->npcs()->count() : 0,
                'items' => $canManageContent ? $game->items()->count() : 0,
                'backgrounds' => $canManageContent ? $game->backgrounds()->count() : 0,
            ],
            'featured_session' => $activeSession ? $this->sessionDashboardPayload($activeSession) : null,
            'recent_sessions' => $recentSessions
                ->map(fn (GameSession $session) => $this->sessionDashboardPayload($session))
                ->values(),
            'active_tickets' => $tickets
                ->map(fn (Ticket $ticket) => [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'status' => $ticket->status->value,
                    'status_label' => $ticket->status->label(),
                    'status_tone' => $ticket->status->tone(),
                    'creator_name' => $ticket->creator?->name,
                    'last_message_at' => $ticket->last_message_at?->toISOString(),
                    'updated_at' => $ticket->updated_at?->toISOString(),
                ])
                ->values(),
            'featured_npc' => $featuredNpc ? $this->npcDashboardPayload($featuredNpc) : null,
            'recent_npcs' => $npcs
                ->map(fn (Npc $npc) => $this->npcDashboardPayload($npc))
                ->values(),
            'recent_items' => $items
                ->map(fn (Item $item) => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'category' => $item->category,
                    'description' => $item->description,
                    'image_url' => $this->publicUrl($item->image_path),
                    'updated_at' => $item->updated_at?->toISOString(),
                ])
                ->values(),
            'party' => $characters
                ->take(6)
                ->map(fn (Character $character) => $this->characterDashboardPayload($character))
                ->values(),
            'selected_character' => $selectedCharacter
                ? $this->characterDashboardPayload($selectedCharacter, includeSheet: true, template: $game->resolvedCharacterTemplate())
                : null,
            'featured_background' => $background ? [
                'id' => $background->id,
                'title' => $background->title,
                'image_url' => $this->publicUrl($background->image_path),
            ] : null,
        ];
    }

    protected function sessionDashboardPayload(GameSession $session): array
    {
        return [
            'id' => $session->id,
            'title' => $session->title,
            'status' => $session->status->value,
            'status_label' => $session->status->label(),
            'started_at' => ($session->started_at ?? $session->created_at)?->toISOString(),
            'ended_at' => $session->ended_at?->toISOString(),
            'participants_count' => $session->participants_count ?? null,
        ];
    }

    protected function npcDashboardPayload(Npc $npc): array
    {
        return [
            'id' => $npc->id,
            'name' => $npc->name,
            'type' => $npc->type->value,
            'type_label' => $npc->type->label(),
            'description' => $npc->description,
            'uses_character_sheet' => (bool) $npc->uses_character_sheet,
            'avatar_url' => $this->publicUrl($npc->avatar_path),
            'updated_at' => $npc->updated_at?->toISOString(),
        ];
    }

    protected function characterDashboardPayload(Character $character, bool $includeSheet = false, array $template = []): array
    {
        $payload = [
            'id' => $character->id,
            'name' => $character->name,
            'origin' => $character->origin,
            'notes' => $character->notes,
            'is_active' => (bool) $character->is_active,
            'avatar_url' => $this->publicUrl($character->avatar_path),
            'user' => $character->user?->only(['id', 'name', 'email']),
        ];

        if (! $includeSheet) {
            return $payload;
        }

        $attributeItems = collect($template['attributes']['items'] ?? []);
        $skillItems = collect($template['skills']['items'] ?? [])
            ->flatMap(fn (array $skill) => [$skill, ...($skill['subskills'] ?? [])])
            ->values();

        return [
            ...$payload,
            'attributes' => $attributeItems
                ->take(8)
                ->map(fn (array $item) => [
                    'key' => $item['key'] ?? $item['label'] ?? '',
                    'label' => $item['label'] ?? $item['key'] ?? 'Attribute',
                    'value' => $character->attribute_values[$item['key'] ?? ''] ?? $item['default'] ?? 0,
                ])
                ->values(),
            'skills' => $skillItems
                ->filter(fn (array $item) => (bool) ($character->skill_values[$item['key'] ?? ''] ?? $item['default'] ?? false))
                ->take(6)
                ->map(fn (array $item) => [
                    'key' => $item['key'] ?? $item['label'] ?? '',
                    'label' => $item['label'] ?? $item['key'] ?? 'Skill',
                ])
                ->values(),
            'extra_fields' => collect($template['extra_fields'] ?? [])
                ->take(4)
                ->map(fn (array $item) => [
                    'key' => $item['key'] ?? $item['label'] ?? '',
                    'label' => $item['label'] ?? $item['key'] ?? 'Field',
                    'value' => $character->extra_field_values[$item['key'] ?? ''] ?? $item['default'] ?? null,
                ])
                ->values(),
        ];
    }

    protected function publicUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
