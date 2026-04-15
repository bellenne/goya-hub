<?php

namespace App\Services\Sessions;

use App\Enums\DiceType;
use App\Enums\DiceRollSourceType;
use App\Models\GameSession;
use App\Models\SessionDiceRoll;
use App\Models\User;
use App\Services\Characters\RollAttributeResolver;

class RollSessionDice
{
    public function __construct(
        protected RollAttributeResolver $rollAttributeResolver,
    ) {}

    public function handle(GameSession $session, User $user, array $data): SessionDiceRoll
    {
        $diceType = DiceType::from($data['dice_type']);
        $diceCount = (int) $data['dice_count'];
        $manualModifier = (int) ($data['modifier'] ?? 0);
        $sourceContext = $this->resolveSourceContext($session, $data);
        $attributeContext = $this->resolveAttributeContext($sourceContext, $data['attribute_key'] ?? null);
        $modifier = $manualModifier + $attributeContext['modifier'];
        $rollValues = [];

        for ($index = 0; $index < $diceCount; $index++) {
            $rollValues[] = random_int(1, $diceType->sides());
        }

        return $session->diceRolls()->create([
            'user_id' => $user->id,
            'source_type' => $sourceContext['type'],
            'source_id' => $sourceContext['id'],
            'source_name' => $sourceContext['name'],
            'dice_count' => $diceCount,
            'dice_type' => $diceType,
            'modifier' => $modifier,
            'manual_modifier' => $manualModifier,
            'attribute_key' => $attributeContext['key'],
            'attribute_label' => $attributeContext['label'],
            'attribute_modifier' => $attributeContext['modifier'],
            'roll_values' => $rollValues,
            'total' => array_sum($rollValues) + $modifier,
        ]);
    }

    protected function resolveSourceContext(GameSession $session, array $data): array
    {
        $type = $data['source_type'] ?? null;
        $id = isset($data['source_id']) ? (int) $data['source_id'] : null;

        if ($type === DiceRollSourceType::Character->value && $id !== null) {
            $character = $session->game->characters()->findOrFail($id);

            return [
                'type' => DiceRollSourceType::Character,
                'id' => $character->id,
                'name' => $character->name,
                'attributes' => $character->attribute_values ?? [],
                'template' => $character->game->resolvedCharacterTemplate(),
            ];
        }

        if ($type === DiceRollSourceType::SceneNpc->value && $id !== null) {
            $sceneNpc = $session->sceneState?->sceneNpcs()->with('npc.game')->findOrFail($id);

            return [
                'type' => DiceRollSourceType::SceneNpc,
                'id' => $sceneNpc->id,
                'name' => $sceneNpc->display_name ?: (($sceneNpc->is_group ? 'Группа ' : '').$sceneNpc->npc->name),
                'attributes' => $sceneNpc->npc->attribute_values ?? [],
                'template' => $sceneNpc->npc->game->resolvedCharacterTemplate(),
            ];
        }

        return [
            'type' => null,
            'id' => null,
            'name' => null,
            'attributes' => [],
            'template' => null,
        ];
    }

    protected function resolveAttributeContext(array $sourceContext, ?string $attributeKey): array
    {
        if ($attributeKey === null || $sourceContext['template'] === null) {
            return [
                'key' => null,
                'label' => null,
                'modifier' => 0,
            ];
        }

        $attribute = $this->rollAttributeResolver->findRollableAttribute($sourceContext['template'], $attributeKey);

        if ($attribute === null) {
            return [
                'key' => null,
                'label' => null,
                'modifier' => 0,
            ];
        }

        return [
            'key' => $attribute['key'],
            'label' => $attribute['label'],
            'modifier' => $this->rollAttributeResolver->modifier(
                $attribute,
                $sourceContext['attributes'][$attribute['key']] ?? $attribute['default'],
            ),
        ];
    }
}
