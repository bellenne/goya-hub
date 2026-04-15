<?php

namespace App\Http\Controllers;

use App\Concerns\StoresPublicUploads;
use App\Enums\SessionStatus;
use App\Events\SessionSceneUpdated;
use App\Http\Requests\UpdateNpcTypeRequest;
use App\Enums\NpcType;
use App\Http\Requests\UpsertNpcRequest;
use App\Models\Game;
use App\Models\Npc;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GameNpcController extends Controller
{
    use StoresPublicUploads;

    public function index(Game $game): Response
    {
        Gate::authorize('manageContent', $game);

        return $this->renderIndex($game);
    }

    public function store(UpsertNpcRequest $request, Game $game): RedirectResponse
    {
        Gate::authorize('manageContent', $game);

        $validated = $request->validated();

        $game->npcs()->create([
            'name' => $validated['name'],
            'avatar_path' => $this->storePublicUpload($validated['avatar'] ?? null, 'npcs'),
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'uses_character_sheet' => (bool) ($validated['uses_character_sheet'] ?? false),
            'attribute_values' => $validated['attribute_values'] ?? [],
            'skill_values' => $validated['skill_values'] ?? [],
            'extra_field_values' => $validated['extra_field_values'] ?? [],
        ]);

        return redirect()->route('games.npcs.index', $game)->with('success', 'NPC создан.');
    }

    public function edit(Game $game, Npc $npc): Response
    {
        Gate::authorize('manageContent', $game);
        abort_unless($npc->game_id === $game->id, 404);

        return $this->renderIndex($game, $npc);
    }

    public function update(UpsertNpcRequest $request, Game $game, Npc $npc): RedirectResponse
    {
        Gate::authorize('manageContent', $game);
        abort_unless($npc->game_id === $game->id, 404);

        $validated = $request->validated();

        $npc->update([
            'name' => $validated['name'],
            'avatar_path' => $this->storePublicUpload($validated['avatar'] ?? null, 'npcs', $npc->avatar_path),
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'uses_character_sheet' => (bool) ($validated['uses_character_sheet'] ?? false),
            'attribute_values' => $validated['attribute_values'] ?? [],
            'skill_values' => $validated['skill_values'] ?? [],
            'extra_field_values' => $validated['extra_field_values'] ?? [],
        ]);

        return redirect()->route('games.npcs.edit', [$game, $npc])->with('success', 'NPC сохранён.');
    }

    public function destroy(Game $game, Npc $npc): RedirectResponse
    {
        Gate::authorize('manageContent', $game);
        abort_unless($npc->game_id === $game->id, 404);

        if ($npc->avatar_path) {
            Storage::disk('public')->delete($npc->avatar_path);
        }

        $npc->delete();

        return redirect()->route('games.npcs.index', $game)->with('success', 'NPC удалён.');
    }

    public function updateType(UpdateNpcTypeRequest $request, Game $game, Npc $npc): RedirectResponse
    {
        Gate::authorize('manageContent', $game);
        abort_unless($npc->game_id === $game->id, 404);

        $npc->update([
            'type' => $request->validated()['type'],
        ]);

        $game->sessions()
            ->where('status', SessionStatus::Active)
            ->get()
            ->each(fn ($session) => broadcast(new SessionSceneUpdated($session))->toOthers());

        $sessionId = $request->validated()['back_to_session_id'] ?? null;
        $session = $sessionId ? $game->sessions()->find($sessionId) : null;

        if ($session !== null) {
            return redirect()->route('games.sessions.show', [$game, $session])->with('success', 'NPC type updated.');
        }

        return redirect()->route('games.npcs.index', $game)->with('success', 'NPC type updated.');
    }

    protected function renderIndex(Game $game, ?Npc $selectedNpc = null): Response
    {
        $template = $game->resolvedCharacterTemplate();
        $characterSheetAvailable = filled($template['attributes']['items'] ?? [])
            || filled($template['skills']['items'] ?? [])
            || filled($template['extra_fields'] ?? []);

        return Inertia::render('Games/Content/Npcs', [
            'game' => [
                'id' => $game->id,
                'name' => $game->name,
            ],
            'template' => $template,
            'characterSheetAvailable' => $characterSheetAvailable,
            'npcTypes' => collect(NpcType::cases())->map(fn (NpcType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
            ]),
            'npcs' => $game->npcs()
                ->latest()
                ->get()
                ->map(fn (Npc $npc) => $this->npcPayload($npc)),
            'selectedNpc' => $selectedNpc ? $this->npcPayload($selectedNpc) : null,
        ]);
    }

    protected function npcPayload(Npc $npc): array
    {
        return [
            'id' => $npc->id,
            'name' => $npc->name,
            'type' => $npc->type->value,
            'type_label' => $npc->type->label(),
            'description' => $npc->description,
            'uses_character_sheet' => (bool) $npc->uses_character_sheet,
            'attribute_values' => $npc->attribute_values ?? [],
            'skill_values' => $npc->skill_values ?? [],
            'extra_field_values' => $npc->extra_field_values ?? [],
            'avatar_url' => $npc->avatar_path ? Storage::disk('public')->url($npc->avatar_path) : null,
        ];
    }
}
