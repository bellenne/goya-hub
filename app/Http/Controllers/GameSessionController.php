<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Enums\SessionMusicPlaybackStatus;
use App\Events\SessionLobbyUpdated;
use App\Events\SessionSceneUpdated;
use App\Http\Requests\JoinGameSessionByCodeRequest;
use App\Http\Requests\StoreGameSessionRequest;
use App\Models\Character;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\SessionDiceRoll;
use App\Services\Characters\RollAttributeResolver;
use App\Services\Sessions\CreateGameSession;
use App\Services\Sessions\EnsureSessionSceneState;
use App\Services\Sessions\JoinGameSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class GameSessionController extends Controller
{
    public function __construct(
        protected RollAttributeResolver $rollAttributeResolver,
    ) {}

    public function index(Game $game): Response
    {
        Gate::authorize('viewSessions', $game);

        return Inertia::render('Games/Sessions/Index', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'can_manage_sessions' => Gate::allows('manageSessions', $game),
            'sessions' => $game->sessions()
                ->latest()
                ->get()
                ->map(fn (GameSession $session) => [
                    'id' => $session->id,
                    'title' => $session->title,
                    'invite_code' => $session->invite_code,
                    'status' => $session->status->value,
                    'status_label' => $session->status->label(),
                    'participants_count' => $session->participants()->count(),
                    'invite_link' => route('sessions.invites.show', $session->invite_token),
                    'started_at' => ($session->started_at ?? $session->created_at)?->toISOString(),
                    'ended_at' => $session->ended_at?->toISOString(),
                    'duration_seconds' => $this->durationSeconds($session),
                    'is_openable' => $session->status !== SessionStatus::Ended,
                    'gm_grace_ends_at' => $session->gm_grace_ends_at?->toISOString(),
                ]),
        ]);
    }

    public function store(StoreGameSessionRequest $request, Game $game, CreateGameSession $createGameSession): RedirectResponse
    {
        Gate::authorize('manageSessions', $game);

        $session = $createGameSession->handle($game, $request->validated());

        return redirect()->route('games.sessions.show', [$game, $session])->with('success', 'Session created.');
    }

    public function show(Game $game, GameSession $session, EnsureSessionSceneState $ensureSessionSceneState): Response
    {
        abort_unless($session->game_id === $game->id, 404);
        Gate::authorize('view', $session);

        $session->load(['participants.user', 'game.characters.user', 'game.backgrounds', 'game.npcs', 'musicState']);
        $sceneState = $ensureSessionSceneState->handle($session);
        $sceneState->load(['background', 'sceneNpcs.npc', 'speaker']);

        if (
            $session->status === SessionStatus::Active
            || (
                $session->status === SessionStatus::GmDisconnectedGrace
                && $session->status_before_gm_disconnect === SessionStatus::Active->value
            )
        ) {
            return Inertia::render('Games/Sessions/Table', [
                'game' => [
                    'id' => $game->id,
                    'name' => $game->name,
                ],
                'session' => $this->sessionPayload($session),
                'scene' => $this->scenePayload($session),
                'rolls' => $this->rollsPayload($session),
                'inventory' => $this->inventoryPayload($session),
                'can_manage_sessions' => Gate::allows('start', $session),
            ]);
        }

        return Inertia::render('Games/Sessions/Show', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'session' => $this->sessionPayload($session),
            'can_manage_sessions' => Gate::allows('start', $session),
        ]);
    }

    public function joinByCode(JoinGameSessionByCodeRequest $request, Game $game, JoinGameSession $joinGameSession): RedirectResponse
    {
        Gate::authorize('viewSessions', $game);

        $session = $game->sessions()
            ->where('invite_code', strtoupper($request->validated()['invite_code']))
            ->firstOrFail();

        $joinGameSession->handle($session, $request->user());

        if (! $game->characters()->where('user_id', $request->user()->id)->exists()) {
            return redirect()
                ->route('games.character.edit', ['game' => $game, 'back_to_session_id' => $session->id])
                ->with('success', 'You joined the session. Create your character before entering the table.');
        }

        return redirect()->route('games.sessions.show', [$game, $session])->with('success', 'Joined session lobby.');
    }

    public function join(Game $game, GameSession $session, JoinGameSession $joinGameSession): RedirectResponse
    {
        abort_unless($session->game_id === $game->id, 404);
        Gate::authorize('join', $session);

        $joinGameSession->handle($session, auth()->user());

        if (! $game->characters()->where('user_id', auth()->id())->exists()) {
            return redirect()
                ->route('games.character.edit', ['game' => $game, 'back_to_session_id' => $session->id])
                ->with('success', 'You joined the session. Create your character before entering the table.');
        }

        return redirect()->route('games.sessions.show', [$game, $session])->with('success', 'Joined session lobby.');
    }

    public function joinByInvite(string $token, JoinGameSession $joinGameSession): RedirectResponse
    {
        $session = GameSession::query()
            ->with('game')
            ->where('invite_token', $token)
            ->firstOrFail();

        $joinGameSession->handle($session, auth()->user(), allowAutoJoinGame: true);

        if (! $session->game->characters()->where('user_id', auth()->id())->exists()) {
            return redirect()
                ->route('games.character.edit', ['game' => $session->game, 'back_to_session_id' => $session->id])
                ->with('success', 'You joined the game and session. Create your character before entering the table.');
        }

        return redirect()->route('games.sessions.show', [$session->game, $session])->with('success', 'Joined session lobby.');
    }

    public function start(Game $game, GameSession $session): RedirectResponse
    {
        abort_unless($session->game_id === $game->id, 404);
        Gate::authorize('start', $session);

        abort_if($session->status === SessionStatus::Ended, HttpResponse::HTTP_CONFLICT);

        $session->update([
            'status' => SessionStatus::Active,
            'started_at' => $session->started_at ?? now(),
        ]);

        broadcast(new SessionLobbyUpdated($session->fresh(['game', 'participants.user'])))->toOthers();
        broadcast(new SessionSceneUpdated($session->fresh()))->toOthers();

        return redirect()->route('games.sessions.show', [$game, $session])->with('success', 'Session started.');
    }

    protected function sessionPayload(GameSession $session): array
    {
        return [
            'id' => $session->id,
            'title' => $session->title,
            'invite_code' => $session->invite_code,
            'invite_link' => route('sessions.invites.show', $session->invite_token),
            'status' => $session->status->value,
            'status_label' => $session->status->label(),
            'status_before_gm_disconnect' => $session->status_before_gm_disconnect,
            'started_at' => ($session->started_at ?? $session->created_at)?->toISOString(),
            'ended_at' => $session->ended_at?->toISOString(),
            'duration_seconds' => $this->durationSeconds($session),
            'gm_grace_started_at' => $session->gm_grace_started_at?->toISOString(),
            'gm_grace_ends_at' => $session->gm_grace_ends_at?->toISOString(),
            'presence_channel' => "session.lobby.{$session->id}",
            'cursor_channel' => "session.cursors.{$session->id}",
            'participants' => $session->participants
                ->sortBy('joined_at')
                ->values()
                ->map(fn ($participant) => [
                    'id' => $participant->id,
                    'joined_at' => $participant->joined_at?->toISOString(),
                    'user' => $participant->user->only(['id', 'name', 'email']),
                ]),
        ];
    }

    protected function scenePayload(GameSession $session): array
    {
        $session->loadMissing([
            'sceneState.background',
            'sceneState.sceneNpcs.npc',
            'sceneState.speaker',
            'game.backgrounds',
            'game.npcs',
            'game.characters.user',
            'musicState',
        ]);

        $sceneState = $session->sceneState;
        $characters = $session->game->characters->sortBy('name')->values();
        $currentUserId = auth()->id();
        $canManageSessions = Gate::allows('start', $session);
        $hiddenCharacterIds = collect($sceneState?->hidden_character_ids ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $visibleTeammates = $canManageSessions
            ? $characters->where('user_id', '!=', $currentUserId)->values()
            : $characters
                ->where('user_id', '!=', $currentUserId)
                ->reject(fn ($character) => $hiddenCharacterIds->contains((int) $character->id))
                ->values();
        $sceneNpcs = $sceneState?->sceneNpcs
            ->sortBy(fn ($sceneNpc) => $sceneNpc->display_name ?? $sceneNpc->npc->name)
            ->values()
            ->map(fn ($sceneNpc) => $this->sceneNpcPayload($sceneNpc)) ?? collect();
        $presentNpcIds = $sceneNpcs
            ->filter(fn (array $npc) => $npc['is_present'])
            ->pluck('id')
            ->values();
        $encounteredNpcIds = $sceneNpcs
            ->filter(fn (array $npc) => $npc['is_encountered'])
            ->pluck('id')
            ->values();
        $sceneNpcQuantities = $sceneNpcs->pluck('quantity', 'id');

        return [
            'channel' => "session.scene.{$session->id}",
            'background' => $sceneState?->background ? [
                'id' => $sceneState->background->id,
                'title' => $sceneState->background->title,
                'image_url' => $sceneState->background->image_path ? Storage::disk('public')->url($sceneState->background->image_path) : null,
            ] : null,
            'music' => $this->musicPayload($session),
            'scene_npcs' => $sceneNpcs,
            'visible_npcs' => $sceneNpcs
                ->where('is_present', true)
                ->values()
                ->all(),
            'present_npcs' => $sceneNpcs
                ->where('is_present', true)
                ->values()
                ->all(),
            'encountered_npcs' => $sceneNpcs
                ->where('is_encountered', true)
                ->values()
                ->all(),
            'speaker' => $sceneState?->speaker ? [
                'type' => class_basename($sceneState->speaker_type) === 'Character' ? 'character' : 'npc',
                'id' => $sceneState->speaker->id,
                'scene_npc_id' => $sceneState->speaker_scene_npc_id,
                'name' => $sceneState->speaker->name,
            ] : null,
            'own_character' => optional($characters->firstWhere('user_id', $currentUserId), function ($character) {
                return $this->sceneCharacterPayload($character);
            }),
            'teammates' => $visibleTeammates
                ->map(fn ($character) => $this->sceneCharacterPayload($character)),
            'controls' => [
                'backgrounds' => $session->game->backgrounds
                    ->sortBy('title')
                    ->values()
                    ->map(fn ($background) => [
                        'id' => $background->id,
                        'title' => $background->title,
                        'image_url' => $background->image_path ? Storage::disk('public')->url($background->image_path) : null,
                    ]),
                'npcs' => $session->game->npcs
                    ->sortBy('name')
                    ->values()
                    ->map(fn ($npc) => [
                        'id' => $npc->id,
                        'name' => $npc->name,
                        'type' => $npc->type->value,
                        'type_label' => $npc->type->label(),
                        'avatar_url' => $npc->avatar_path ? Storage::disk('public')->url($npc->avatar_path) : null,
                        'description' => $npc->description,
                        'has_character_sheet' => (bool) $npc->uses_character_sheet,
                        'is_encountered' => $encounteredNpcIds->contains($npc->id),
                        'is_present' => $presentNpcIds->contains($npc->id),
                        'quantity' => (int) ($sceneNpcQuantities->get($npc->id) ?? 1),
                    ]),
                'speakers' => [
                    'characters' => $characters->map(fn ($character) => [
                        'id' => $character->id,
                        'name' => $character->name,
                    ])->values(),
                    'npcs' => $session->game->npcs
                        ->sortBy('name')
                        ->values()
                        ->map(fn ($npc) => [
                            'id' => $npc->id,
                            'name' => $npc->name,
                            'type' => $npc->type->value,
                        ]),
                ],
                'visible_npc_ids' => $presentNpcIds,
                'present_npc_ids' => $presentNpcIds,
                'encountered_npc_ids' => $encounteredNpcIds,
                'npc_scene_quantities' => $sceneNpcQuantities,
                'hidden_character_ids' => $hiddenCharacterIds,
                'current_background_id' => $sceneState?->background_id,
            ],
        ];
    }

    protected function rollsPayload(GameSession $session): array
    {
        $session->loadMissing([
            'diceRolls' => fn ($query) => $query->latest()->limit(20),
            'diceRolls.user',
        ]);

        return [
            'channel' => "session.rolls.{$session->id}",
            'items' => $session->diceRolls
                ->sortByDesc('created_at')
                ->values()
                ->map(fn (SessionDiceRoll $roll) => [
                    'id' => $roll->id,
                    'notation' => sprintf('%dd%s%+d', $roll->dice_count, $roll->dice_type->value, $roll->modifier),
                    'dice_count' => $roll->dice_count,
                    'dice_type' => $roll->dice_type->value,
                    'modifier' => $roll->modifier,
                    'manual_modifier' => $roll->manual_modifier,
                    'attribute_modifier' => $roll->attribute_modifier,
                    'attribute_key' => $roll->attribute_key,
                    'attribute_label' => $roll->attribute_label,
                    'actor_name' => $roll->source_name ?: $roll->user->name,
                    'actor_type' => $roll->source_type?->value,
                    'actor_id' => $roll->source_id,
                    'roll_values' => $roll->roll_values,
                    'random_source' => $roll->random_source,
                    'total' => $roll->total,
                    'rolled_at' => $roll->created_at?->toISOString(),
                    'user' => [
                        'id' => $roll->user->id,
                        'name' => $roll->user->name,
                    ],
                ]),
        ];
    }

    protected function musicPayload(GameSession $session): array
    {
        $musicState = $session->musicState;

        if ($musicState === null) {
            return [
                'source_type' => null,
                'title' => null,
                'audio_url' => null,
                'youtube_url' => null,
                'playback_status' => SessionMusicPlaybackStatus::Stopped->value,
                'position_seconds' => 0,
                'started_at' => null,
                'updated_at' => null,
            ];
        }

        return [
            'source_type' => $musicState->source_type?->value,
            'title' => $musicState->title,
            'audio_url' => $musicState->file_path ? Storage::disk('public')->url($musicState->file_path) : $musicState->direct_url,
            'youtube_url' => $musicState->youtube_url,
            'playback_status' => $musicState->playback_status->value,
            'position_seconds' => $musicState->position_seconds,
            'started_at' => $musicState->started_at?->toISOString(),
            'updated_at' => $musicState->updated_at?->toISOString(),
        ];
    }

    protected function sceneCharacterPayload(Character $character): array
    {
        return [
            'id' => $character->id,
            'name' => $character->name,
            'origin' => $character->origin,
            'notes' => $character->notes,
            'avatar_url' => $character->avatar_path ? Storage::disk('public')->url($character->avatar_path) : null,
            'user_name' => $character->user?->name,
            'attribute_values' => $character->attribute_values ?? [],
            'skill_values' => $character->skill_values ?? [],
            'extra_field_values' => $character->extra_field_values ?? [],
            'template' => $character->game->resolvedCharacterTemplate(),
            'rollable_attributes' => $this->rollAttributeResolver->rollableAttributes($character->game->resolvedCharacterTemplate()),
        ];
    }

    protected function sceneNpcPayload($sceneNpc): array
    {
        $npc = $sceneNpc->npc;
        $displayName = $sceneNpc->display_name
            ?? (($sceneNpc->is_group ? 'Группа ' : '').$npc->name);

        return [
            'id' => $sceneNpc->id,
            'npc_id' => $npc->id,
            'name' => $displayName,
            'base_name' => $npc->name,
            'type' => $sceneNpc->scene_type ?? $npc->type->value,
            'type_label' => ucfirst($sceneNpc->scene_type ?? $npc->type->value),
            'avatar_url' => $npc->avatar_path ? Storage::disk('public')->url($npc->avatar_path) : null,
            'description' => $npc->description,
            'has_character_sheet' => (bool) $npc->uses_character_sheet,
            'attribute_values' => $npc->uses_character_sheet ? ($npc->attribute_values ?? []) : [],
            'rollable_attributes' => $npc->uses_character_sheet
                ? $this->rollAttributeResolver->rollableAttributes($npc->game->resolvedCharacterTemplate())
                : [],
            'is_encountered' => $sceneNpc->is_encountered,
            'is_present' => $sceneNpc->is_present,
            'quantity' => 1,
            'is_group' => $sceneNpc->is_group,
            'group_size' => $sceneNpc->group_size,
        ];
    }

    protected function inventoryPayload(GameSession $session): array
    {
        $session->loadMissing([
            'game.characters.user',
            'game.characters.inventoryItems.item',
            'game.items',
        ]);

        $currentUserId = auth()->id();
        $canManageInventory = Gate::allows('start', $session);
        $characters = $session->game->characters
            ->sortBy('name')
            ->values();

        return [
            'channel' => "session.inventory.{$session->id}",
            'own_character' => optional(
                $characters->firstWhere('user_id', $currentUserId),
                fn (Character $character) => $this->characterInventoryPayload($character),
            ),
            'characters' => $canManageInventory
                ? $characters->map(fn (Character $character) => $this->characterInventoryPayload($character))->values()
                : [],
            'catalog_items' => $canManageInventory
                ? $session->game->items
                    ->sortBy('name')
                    ->values()
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'category' => $item->category,
                    ])
                : [],
        ];
    }

    protected function characterInventoryPayload(Character $character): array
    {
        return [
            'id' => $character->id,
            'name' => $character->name,
            'origin' => $character->origin,
            'notes' => $character->notes,
            'avatar_url' => $character->avatar_path ? Storage::disk('public')->url($character->avatar_path) : null,
            'user_name' => $character->user?->name,
            'attribute_values' => $character->attribute_values ?? [],
            'skill_values' => $character->skill_values ?? [],
            'extra_field_values' => $character->extra_field_values ?? [],
            'template' => $character->game->resolvedCharacterTemplate(),
            'inventory_items' => $character->inventoryItems
                ->sortBy('id')
                ->values()
                ->map(fn ($inventoryItem) => [
                    'id' => $inventoryItem->id,
                    'item_id' => $inventoryItem->item_id,
                    'name' => $inventoryItem->resolvedName(),
                    'description' => $inventoryItem->resolvedDescription(),
                    'image_url' => $inventoryItem->resolvedImagePath()
                        ? Storage::disk('public')->url($inventoryItem->resolvedImagePath())
                        : null,
                    'quantity' => $inventoryItem->quantity,
                    'is_custom' => $inventoryItem->item_id === null,
                ]),
        ];
    }

    protected function durationSeconds(GameSession $session): ?int
    {
        if ($session->ended_at === null) {
            return null;
        }

        return (int) ($session->started_at ?? $session->created_at)->diffInSeconds($session->ended_at);
    }
}
