<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertCharacterRequest;
use App\Http\Requests\UpdateCharacterSheetRequest;
use App\Events\SessionSceneUpdated;
use App\Enums\SessionStatus;
use App\Models\Character;
use App\Models\Game;
use App\Services\Characters\UpsertCharacter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CharacterController extends Controller
{
    public function edit(Request $request, Game $game): Response
    {
        Gate::authorize('createCharacter', $game);

        $character = $game->characters()
            ->where('user_id', auth()->id())
            ->first();

        return Inertia::render('Games/Characters/Edit', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'template' => $game->resolvedCharacterTemplate(),
            'character' => $character ? $this->characterPayload($character) : null,
            'back_to_session' => optional(
                $request->integer('back_to_session_id')
                    ? $game->sessions()->find($request->integer('back_to_session_id'))
                    : null,
                fn ($session) => [
                    'id' => $session->id,
                    'title' => $session->title,
                ],
            ),
        ]);
    }

    public function upsert(UpsertCharacterRequest $request, Game $game, UpsertCharacter $upsertCharacter): RedirectResponse
    {
        Gate::authorize('createCharacter', $game);

        $validated = $request->validated();

        $upsertCharacter->handle($game, $request->user(), $validated);

        $sessionId = $validated['back_to_session_id'] ?? null;
        $session = $sessionId ? $game->sessions()->find($sessionId) : null;

        if ($session !== null) {
            return redirect()
                ->route('games.sessions.show', [$game, $session])
                ->with('success', 'Character saved. You can join the session now.');
        }

        return redirect()
            ->route('games.character.edit', $game)
            ->with('success', 'Character saved.');
    }

    public function index(Game $game): Response
    {
        Gate::authorize('viewCharacters', $game);

        $characters = $game->characters()
            ->with('user')
            ->orderBy('name')
            ->get()
            ->map(fn (Character $character) => [
                'id' => $character->id,
                'name' => $character->name,
                'origin' => $character->origin,
                'avatar_url' => $character->avatar_path ? Storage::disk('public')->url($character->avatar_path) : null,
                'user' => $character->user->only(['id', 'name', 'email']),
            ]);

        return Inertia::render('Games/Characters/Index', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'characters' => $characters,
        ]);
    }

    public function show(Game $game, Character $character): Response
    {
        abort_unless($character->game_id === $game->id, 404);

        $character->load('user', 'game', 'inventoryItems.item');

        Gate::authorize('view', $character);

        return Inertia::render('Games/Characters/Show', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'character' => $this->characterPayload($character),
            'template' => $game->resolvedCharacterTemplate(),
            'catalogItems' => Gate::allows('manageInventory', $character)
                ? $game->items()
                    ->orderBy('name')
                    ->get()
                    ->map(fn ($item) => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'category' => $item->category,
                    ])
                : [],
            'can_manage_inventory' => Gate::allows('manageInventory', $character),
        ]);
    }

    public function updateSheet(UpdateCharacterSheetRequest $request, Game $game, Character $character): RedirectResponse
    {
        abort_unless($character->game_id === $game->id, 404);

        $validated = $request->validated();

        $character->update([
            'attribute_values' => $validated['attribute_values'],
            'skill_values' => $validated['skill_values'],
            'extra_field_values' => $validated['extra_field_values'] ?? [],
        ]);

        $game->sessions()
            ->where('status', SessionStatus::Active)
            ->get()
            ->each(fn ($session) => broadcast(new SessionSceneUpdated($session))->toOthers());

        $session = isset($validated['back_to_session_id'])
            ? $game->sessions()->find($validated['back_to_session_id'])
            : null;

        if ($session) {
            return redirect()->route('games.sessions.show', [$game, $session])->with('success', 'Character sheet updated.');
        }

        return redirect()->route('games.characters.show', [$game, $character])->with('success', 'Character sheet updated.');
    }

    protected function characterPayload(Character $character): array
    {
        return [
            'id' => $character->id,
            'name' => $character->name,
            'origin' => $character->origin,
            'notes' => $character->notes,
            'avatar_url' => $character->avatar_path ? Storage::disk('public')->url($character->avatar_path) : null,
            'attribute_values' => $character->attribute_values,
            'skill_values' => $character->skill_values,
            'extra_field_values' => $character->extra_field_values,
            'inventory_items' => $character->inventoryItems
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
            'user' => $character->user?->only(['id', 'name', 'email']),
        ];
    }
}
