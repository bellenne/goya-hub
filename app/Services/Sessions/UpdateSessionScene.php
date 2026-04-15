<?php

namespace App\Services\Sessions;

use App\Events\SessionSceneUpdated;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\Npc;

class UpdateSessionScene
{
    public function handle(GameSession $session, array $attributes): void
    {
        $sceneState = $session->sceneState()->firstOrCreate();

        if (array_key_exists('background_id', $attributes)) {
            $sceneState->background_id = $attributes['background_id'];
        }

        if (array_key_exists('speaker_type', $attributes)) {
            if ($attributes['speaker_type'] === null || $attributes['speaker_id'] === null) {
                $sceneState->speaker()->dissociate();
                $sceneState->speaker_scene_npc_id = null;
            } else {
                $speaker = match ($attributes['speaker_type']) {
                    'character' => Character::query()->findOrFail($attributes['speaker_id']),
                    'npc' => Npc::query()->findOrFail($attributes['speaker_id']),
                };

                $sceneState->speaker()->associate($speaker);
                $sceneState->speaker_scene_npc_id = $attributes['speaker_type'] === 'npc'
                    ? ($attributes['speaker_scene_npc_id'] ?? null)
                    : null;
            }
        }

        if (array_key_exists('hidden_character_ids', $attributes)) {
            $sceneState->hidden_character_ids = collect($attributes['hidden_character_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        $sceneState->save();

        if (
            array_key_exists('visible_npc_ids', $attributes)
            || array_key_exists('encountered_npc_ids', $attributes)
            || array_key_exists('present_npc_ids', $attributes)
        ) {
            $presentIds = collect($attributes['present_npc_ids'] ?? $attributes['visible_npc_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();
            $encounteredIds = collect($attributes['encountered_npc_ids'] ?? $attributes['visible_npc_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->merge($presentIds)
                ->unique()
                ->values();
            $quantities = collect($attributes['npc_scene_quantities'] ?? []);

            $sync = $encounteredIds
                ->mapWithKeys(function (int $npcId) use ($presentIds, $quantities) {
                    return [
                        $npcId => [
                            'is_encountered' => true,
                            'is_present' => $presentIds->contains($npcId),
                            'quantity' => max(1, min(99, (int) ($quantities->get($npcId) ?? $quantities->get((string) $npcId) ?? 1))),
                        ],
                    ];
                })
                ->all();

            $sceneState->visibleNpcs()->sync($sync);
        }

        broadcast(new SessionSceneUpdated($session->fresh()))->toOthers();
    }
}
