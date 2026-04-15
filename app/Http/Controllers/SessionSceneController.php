<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSessionSceneRequest;
use App\Events\SessionSceneUpdated;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\Npc;
use App\Models\SessionSceneNpc;
use App\Services\Sessions\UpdateSessionScene;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SessionSceneController extends Controller
{
    public function update(UpdateSessionSceneRequest $request, Game $game, GameSession $session, UpdateSessionScene $updateSessionScene): RedirectResponse
    {
        abort_unless($session->game_id === $game->id, 404);

        $updateSessionScene->handle($session, $request->validated());

        return redirect()->route('games.sessions.show', [$game, $session])->with('success', 'Scene updated.');
    }

    public function storeNpc(Request $request, Game $game, GameSession $session): RedirectResponse
    {
        abort_unless($session->game_id === $game->id, 404);
        Gate::authorize('start', $session);

        $validated = $request->validate([
            'npc_id' => ['required', 'integer', 'exists:npcs,id'],
            'scene_type' => ['required', Rule::in(['ally', 'neutral', 'enemy'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'create_group' => ['nullable', 'boolean'],
        ]);

        $npc = Npc::query()->where('game_id', $game->id)->findOrFail($validated['npc_id']);
        $sceneState = $session->sceneState()->firstOrCreate();
        $quantity = (int) $validated['quantity'];

        if ($request->boolean('create_group')) {
            $sceneState->sceneNpcs()->create([
                'npc_id' => $npc->id,
                'scene_type' => $validated['scene_type'],
                'display_name' => 'Группа '.$npc->name,
                'is_group' => true,
                'group_size' => $quantity,
                'is_encountered' => true,
                'is_present' => true,
                'quantity' => 1,
            ]);
        } else {
            for ($i = 0; $i < $quantity; $i++) {
                $sceneState->sceneNpcs()->create([
                    'npc_id' => $npc->id,
                    'scene_type' => $validated['scene_type'],
                    'is_group' => false,
                    'is_encountered' => true,
                    'is_present' => true,
                    'quantity' => 1,
                ]);
            }
        }

        broadcast(new SessionSceneUpdated($session->fresh()))->toOthers();

        return redirect()->route('games.sessions.show', [$game, $session])->with('success', 'NPC added to scene.');
    }

    public function updateNpc(Request $request, Game $game, GameSession $session, SessionSceneNpc $sceneNpc): RedirectResponse
    {
        abort_unless($session->game_id === $game->id, 404);
        Gate::authorize('start', $session);

        $sceneState = $session->sceneState()->firstOrCreate();
        abort_unless($sceneNpc->session_scene_state_id === $sceneState->id, 404);

        $validated = $request->validate([
            'scene_type' => ['nullable', Rule::in(['ally', 'neutral', 'enemy'])],
            'is_present' => ['nullable', 'boolean'],
            'is_encountered' => ['nullable', 'boolean'],
        ]);

        $sceneNpc->update($validated);

        broadcast(new SessionSceneUpdated($session->fresh()))->toOthers();

        return redirect()->route('games.sessions.show', [$game, $session])->with('success', 'Scene NPC updated.');
    }
}
