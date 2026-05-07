<?php

namespace App\Http\Requests;

use App\Enums\NpcType;
use App\Models\Game;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpsertNpcRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Game $game */
        $game = $this->route('game');

        return $this->user() !== null && $this->user()->can('manageContent', $game);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:51200'],
            'type' => ['required', Rule::enum(NpcType::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'uses_character_sheet' => ['required', 'boolean'],
            'attribute_values' => ['nullable', 'array'],
            'attribute_values.*' => ['nullable', 'integer'],
            'skill_values' => ['nullable', 'array'],
            'skill_values.*' => ['nullable', 'boolean'],
            'extra_field_values' => ['nullable', 'array'],
            'extra_field_values.*' => ['nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $game = $this->route('game');
        $template = $game instanceof Game ? $game->resolvedCharacterTemplate() : [];
        $usesCharacterSheet = filter_var($this->input('uses_character_sheet'), FILTER_VALIDATE_BOOLEAN);

        $attributeValues = [];
        foreach (($template['attributes']['items'] ?? []) as $item) {
            $attributeValues[$item['key']] = $usesCharacterSheet
                ? (int) $this->input("attribute_values.{$item['key']}", $item['default'] ?? 0)
                : null;
        }

        $skillValues = [];
        foreach (collect($template['skills']['items'] ?? [])->flatMap(fn (array $skill) => [$skill, ...($skill['subskills'] ?? [])]) as $item) {
            $skillValues[$item['key']] = $usesCharacterSheet
                ? filter_var($this->input("skill_values.{$item['key']}", $item['default'] ?? false), FILTER_VALIDATE_BOOLEAN)
                : false;
        }

        $extraFieldValues = [];
        foreach (($template['extra_fields'] ?? []) as $item) {
            $default = $item['default'] ?? ($item['type'] === 'number' ? 0 : '');
            $value = $this->input("extra_field_values.{$item['key']}", $default);

            if (! $usesCharacterSheet) {
                $value = $item['type'] === 'number' ? null : '';
            }

            $extraFieldValues[$item['key']] = $item['type'] === 'number' && $value !== null && $value !== ''
                ? (int) $value
                : $value;
        }

        $this->merge([
            'uses_character_sheet' => $usesCharacterSheet,
            'attribute_values' => $attributeValues,
            'skill_values' => $skillValues,
            'extra_field_values' => $extraFieldValues,
        ]);
    }
}
