<?php

namespace App\Http\Requests;

use App\Enums\DiceType;
use App\Enums\DiceRollSourceType;
use App\Models\Game;
use App\Services\Characters\RollAttributeResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSessionDiceRollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'dice_count' => ['required', 'integer', 'min:1', 'max:20'],
            'dice_type' => ['required', 'string', Rule::in(DiceType::values())],
            'modifier' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'source_type' => ['nullable', 'string', Rule::in(DiceRollSourceType::values())],
            'source_id' => ['nullable', 'integer'],
            'attribute_key' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            /** @var Game $game */
            $game = $this->route('game');
            $session = $this->route('session');
            $sourceType = $this->input('source_type');
            $sourceId = $this->input('source_id');
            $attributeKey = $this->input('attribute_key');

            if (($sourceType === null) !== ($sourceId === null)) {
                $validator->errors()->add('source_id', 'Источник броска должен быть указан полностью.');
                return;
            }

            if ($attributeKey !== null && $sourceType === null) {
                $validator->errors()->add('attribute_key', 'Для выбора характеристики нужен источник броска.');
                return;
            }

            $rollAttributeResolver = app(RollAttributeResolver::class);

            if ($sourceType === DiceRollSourceType::Character->value) {
                $character = $game->characters()->find((int) $sourceId);

                if ($character === null) {
                    $validator->errors()->add('source_id', 'Персонаж для броска не найден.');
                    return;
                }

                if ($character->user_id !== $this->user()->id && ! $this->user()->can('start', $session)) {
                    $validator->errors()->add('source_id', 'Нельзя бросать за этого персонажа.');
                    return;
                }

                if ($attributeKey !== null && $rollAttributeResolver->findRollableAttribute($character->game->resolvedCharacterTemplate(), $attributeKey) === null) {
                    $validator->errors()->add('attribute_key', 'Эта характеристика не участвует в броске.');
                }

                return;
            }

            if ($sourceType === DiceRollSourceType::SceneNpc->value) {
                if (! $this->user()->can('start', $session)) {
                    $validator->errors()->add('source_id', 'Только GM может бросать за NPC.');
                    return;
                }

                $sceneNpc = $session->sceneState?->sceneNpcs()->with('npc.game')->find((int) $sourceId);

                if ($sceneNpc === null || (! $sceneNpc->is_present && ! $sceneNpc->is_encountered)) {
                    $validator->errors()->add('source_id', 'NPC недоступен для броска.');
                    return;
                }

                if ($attributeKey !== null) {
                    if (! $sceneNpc->npc->uses_character_sheet) {
                        $validator->errors()->add('attribute_key', 'У этого NPC нет подключённого листа характеристик.');
                        return;
                    }

                    if ($rollAttributeResolver->findRollableAttribute($sceneNpc->npc->game->resolvedCharacterTemplate(), $attributeKey) === null) {
                        $validator->errors()->add('attribute_key', 'Эта характеристика не участвует в броске.');
                    }
                }

                return;
            }

            if ($sourceType !== null) {
                $validator->errors()->add('source_type', 'Неизвестный источник броска.');
            }
        });
    }
}
