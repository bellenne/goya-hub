<?php

namespace App\Http\Requests;

use App\Models\Character;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCharacterSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Character $character */
        $character = $this->route('character');

        return $this->user() !== null && $this->user()->can('manageInventory', $character);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attribute_values' => ['required', 'array'],
            'attribute_values.*' => ['nullable', 'integer'],
            'skill_values' => ['required', 'array'],
            'skill_values.*' => ['nullable', 'boolean'],
            'extra_field_values' => ['nullable', 'array'],
            'extra_field_values.*' => ['nullable'],
            'back_to_session_id' => ['nullable', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        /** @var Character|null $character */
        $character = $this->route('character');

        if (! $character instanceof Character) {
            return;
        }

        $template = $character->game->resolvedCharacterTemplate();
        $templateAttributes = $template['attributes']['items'] ?? [];
        $templateSkills = $this->skillItems($template);
        $templateExtraFields = $template['extra_fields'] ?? [];

        $attributeValues = [];
        foreach ($templateAttributes as $item) {
            $defaultValue = data_get($character->attribute_values ?? [], $item['key'], $item['default'] ?? 0);

            $attributeValues[$item['key']] = (int) $this->input("attribute_values.{$item['key']}", $defaultValue);
        }
        if ($templateAttributes === []) {
            $attributeValues = collect($this->input('attribute_values', []))
                ->map(fn ($value) => $value !== null && $value !== '' ? (int) $value : null)
                ->all();
        }

        $skillValues = [];
        foreach ($templateSkills as $item) {
            $defaultValue = data_get($character->skill_values ?? [], $item['key'], $item['default'] ?? false);

            $skillValues[$item['key']] = $this->normalizeBoolean(
                $this->input("skill_values.{$item['key']}", $defaultValue),
                (bool) $defaultValue,
            );
        }
        if ($templateSkills === []) {
            $skillValues = collect($this->input('skill_values', []))
                ->map(fn ($value) => $this->normalizeBoolean($value))
                ->all();
        }

        $extraFieldValues = [];
        foreach ($templateExtraFields as $item) {
            $defaultValue = data_get($character->extra_field_values ?? [], $item['key'], $item['default'] ?? ($item['type'] === 'number' ? 0 : ''));
            $value = $this->input("extra_field_values.{$item['key']}", $defaultValue);

            $extraFieldValues[$item['key']] = $item['type'] === 'number' && $value !== null && $value !== ''
                ? (int) $value
                : $value;
        }
        if ($templateExtraFields === []) {
            $extraFieldValues = $this->input('extra_field_values', []);
        }

        $this->merge([
            'attribute_values' => $attributeValues,
            'skill_values' => $skillValues,
            'extra_field_values' => $extraFieldValues,
        ]);
    }

    protected function skillItems(array $template): array
    {
        return collect($template['skills']['items'] ?? [])
            ->flatMap(fn (array $skill) => [$skill, ...($skill['subskills'] ?? [])])
            ->values()
            ->all();
    }

    protected function normalizeBoolean(mixed $value, bool $default = false): bool
    {
        if ($value === null) {
            return $default;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $normalized ?? $default;
    }
}
