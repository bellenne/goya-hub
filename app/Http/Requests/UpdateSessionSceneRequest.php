<?php

namespace App\Http\Requests;

use App\Models\Background;
use App\Models\Character;
use App\Models\GameSession;
use App\Models\Npc;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSessionSceneRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var GameSession $session */
        $session = $this->route('session');

        return $this->user() !== null && $this->user()->can('start', $session);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'background_id' => ['nullable', 'integer', 'exists:backgrounds,id'],
            'visible_npc_ids' => ['nullable', 'array'],
            'visible_npc_ids.*' => ['integer', 'exists:npcs,id'],
            'encountered_npc_ids' => ['nullable', 'array'],
            'encountered_npc_ids.*' => ['integer', 'exists:npcs,id'],
            'present_npc_ids' => ['nullable', 'array'],
            'present_npc_ids.*' => ['integer', 'exists:npcs,id'],
            'hidden_character_ids' => ['nullable', 'array'],
            'hidden_character_ids.*' => ['integer', 'exists:characters,id'],
            'npc_scene_quantities' => ['nullable', 'array'],
            'npc_scene_quantities.*' => ['integer', 'min:1', 'max:99'],
            'speaker_type' => ['nullable', Rule::in(['character', 'npc'])],
            'speaker_id' => ['nullable', 'integer'],
            'speaker_scene_npc_id' => ['nullable', 'integer', 'exists:session_scene_state_npcs,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        /** @var GameSession $session */
        $session = $this->route('session');

        $validator->after(function (Validator $validator) use ($session) {
            $backgroundId = $this->input('background_id');

            if ($backgroundId !== null) {
                $background = Background::query()->find($backgroundId);
                if ($background === null || $background->game_id !== $session->game_id) {
                    $validator->errors()->add('background_id', 'Background does not belong to this game.');
                }
            }

            foreach (['visible_npc_ids', 'encountered_npc_ids', 'present_npc_ids'] as $field) {
                foreach ($this->input($field, []) as $npcId) {
                    $npc = Npc::query()->find($npcId);
                    if ($npc === null || $npc->game_id !== $session->game_id) {
                        $validator->errors()->add($field, 'NPC does not belong to this game.');
                        break 2;
                    }
                }
            }

            foreach ($this->input('hidden_character_ids', []) as $characterId) {
                $character = Character::query()->find($characterId);
                if ($character === null || $character->game_id !== $session->game_id) {
                    $validator->errors()->add('hidden_character_ids', 'Character does not belong to this game.');
                    break;
                }
            }

            $speakerType = $this->input('speaker_type');
            $speakerId = $this->input('speaker_id');

            if (($speakerType === null) xor ($speakerId === null)) {
                $validator->errors()->add('speaker_id', 'Speaker type and speaker id must be set together.');

                return;
            }

            if ($speakerType === 'character') {
                $character = Character::query()->find($speakerId);
                if ($character === null || $character->game_id !== $session->game_id) {
                    $validator->errors()->add('speaker_id', 'Character does not belong to this game.');
                }
            }

            if ($speakerType === 'npc') {
                $npc = Npc::query()->find($speakerId);
                if ($npc === null || $npc->game_id !== $session->game_id) {
                    $validator->errors()->add('speaker_id', 'NPC does not belong to this game.');
                }

                $sceneNpcId = $this->input('speaker_scene_npc_id');
                if ($sceneNpcId !== null) {
                    $sceneState = $session->sceneState;
                    $sceneNpcBelongsToSession = $sceneState !== null
                        && $sceneState->sceneNpcs()->whereKey($sceneNpcId)->where('npc_id', $speakerId)->exists();

                    if (! $sceneNpcBelongsToSession) {
                        $validator->errors()->add('speaker_scene_npc_id', 'Scene NPC does not belong to this session.');
                    }
                }
            }
        });
    }
}
