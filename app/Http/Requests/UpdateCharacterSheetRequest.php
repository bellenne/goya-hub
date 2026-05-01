<?php

namespace App\Http\Requests;

use App\Models\Character;
use App\Services\Characters\AttributePointBalance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
        $rules = [
            'attribute_values' => ['required', 'array'],
            'attribute_values.*' => ['nullable', 'integer'],
            'skill_values' => ['required', 'array'],
            'skill_values.*' => ['nullable', 'boolean'],
            'extra_field_values' => ['nullable', 'array'],
            'extra_field_values.*' => ['nullable'],
            'back_to_session_id' => ['nullable', 'integer'],
        ];

        /** @var Character|null $character */
        $character = $this->route('character');

        if ($character instanceof Character) {
            foreach (($character->game->resolvedCharacterTemplate()['attributes']['items'] ?? []) as $item) {
                $rules["attribute_values.{$item['key']}"] = $this->numberRules($item);
            }
        }

        return $rules;
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

    public function withValidator(Validator $validator): void
    {
        /** @var Character|null $character */
        $character = $this->route('character');

        if (! $character instanceof Character) {
            return;
        }

        $template = $character->game->resolvedCharacterTemplate();

        $validator->after(function (Validator $validator) use ($template) {
            $attributeBalance = AttributePointBalance::calculate(
                $this->input('attribute_values', []),
                $template['attributes']['items'] ?? [],
                (int) ($template['attributes']['points'] ?? 0),
            );

            if ($attributeBalance['available'] < 0) {
                $validator->errors()->add('attribute_values', 'Attribute free points balance is negative.');
            }
        });
    }

    protected function numberRules(array $item): array
    {
        $rules = ['required', 'integer'];

        if (($item['min'] ?? null) !== null) {
            $rules[] = 'min:'.$item['min'];
        }

        if (($item['max'] ?? null) !== null) {
            $rules[] = 'max:'.$item['max'];
        }

        return $rules;
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
