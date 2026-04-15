<?php

namespace App\Http\Requests;

use App\Models\Game;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpsertCharacterRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Game $game */
        $game = $this->route('game');

        return $this->user() !== null && $this->user()->can('createCharacter', $game);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Game $game */
        $game = $this->route('game');
        $template = $game->resolvedCharacterTemplate();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'origin' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'attribute_values' => ['required', 'array'],
            'skill_values' => ['required', 'array'],
            'extra_field_values' => ['nullable', 'array'],
            'back_to_session_id' => ['nullable', 'integer'],
        ];

        foreach ($template['attributes']['items'] as $item) {
            $rules["attribute_values.{$item['key']}"] = $this->numberRules($item);
        }

        foreach ($this->skillItems($template) as $item) {
            $rules["skill_values.{$item['key']}"] = ['required', 'boolean'];
        }

        foreach ($template['extra_fields'] as $item) {
            $fieldRules = [];

            if (($item['required'] ?? false) === true) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            if ($item['type'] === 'number') {
                $fieldRules[] = 'integer';
                if (($item['min'] ?? null) !== null) {
                    $fieldRules[] = 'min:'.$item['min'];
                }
                if (($item['max'] ?? null) !== null) {
                    $fieldRules[] = 'max:'.$item['max'];
                }
            } else {
                $fieldRules[] = 'string';
                $fieldRules[] = 'max:'.($item['max_length'] ?? 255);
            }

            $rules["extra_field_values.{$item['key']}"] = $fieldRules;
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        /** @var Game $game */
        $game = $this->route('game');
        $template = $game->resolvedCharacterTemplate();

        $validator->after(function (Validator $validator) use ($template) {
            $this->validatePoints($validator, $template);

            $sessionId = $this->input('back_to_session_id');
            if ($sessionId !== null) {
                /** @var Game $game */
                $game = $this->route('game');

                if (! $game->sessions()->whereKey($sessionId)->exists()) {
                    $validator->errors()->add('back_to_session_id', 'Session does not belong to this game.');
                }
            }
        });
    }

    protected function validatePoints(Validator $validator, array $template): void
    {
        $attributeSpent = $this->spentPoints(
            $this->input('attribute_values', []),
            $template['attributes']['items'],
        );

        if ($attributeSpent > $template['attributes']['points']) {
            $validator->errors()->add('attribute_values', 'Attribute free points limit exceeded.');
        }

        foreach (($template['points'] ?? []) as $poolKey => $poolLimit) {
            $poolFields = array_values(array_filter(
                $template['extra_fields'],
                fn (array $item) => ($item['type'] ?? null) === 'number'
                    && ($item['points_pool'] ?? null) === $poolKey,
            ));

            $spent = $this->spentPoints(
                $this->input('extra_field_values', []),
                $poolFields,
            );

            if ($spent > $poolLimit) {
                $validator->errors()->add('extra_field_values', 'Extra field free points limit exceeded.');
            }
        }
    }

    protected function spentPoints(array $submittedValues, array $templateItems): int
    {
        return collect($templateItems)->sum(function (array $item) use ($submittedValues) {
            $value = (int) ($submittedValues[$item['key']] ?? $item['default'] ?? 0);
            $default = (int) ($item['default'] ?? 0);

            return max(0, $value - $default);
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
}
