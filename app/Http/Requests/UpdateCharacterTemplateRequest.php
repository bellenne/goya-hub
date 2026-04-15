<?php

namespace App\Http\Requests;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateCharacterTemplateRequest extends FormRequest
{
    protected array $transliterationMap = [
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'e',
        'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm',
        'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
        'ф' => 'f', 'х' => 'h', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sch', 'ъ' => '',
        'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
    ];

    public function authorize(): bool
    {
        /** @var Game $game */
        $game = $this->route('game');

        return $this->user() !== null && $this->user()->can('manageContent', $game);
    }

    public function rules(): array
    {
        return [
            'attributes.points' => ['required', 'integer', 'min:0', 'max:100'],
            'attributes.items' => ['required', 'array', 'min:0', 'max:24'],
            'attributes.items.*.label' => ['required', 'string', 'max:80'],
            'attributes.items.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:60'],
            'attributes.items.*.default' => ['required', 'integer', 'min:-100', 'max:100'],
            'attributes.items.*.min' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'attributes.items.*.max' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'attributes.items.*.roll.enabled' => ['boolean'],
            'attributes.items.*.roll.dice' => ['required', 'in:d4,d6,d8,d10,d12,d20,d100'],
            'attributes.items.*.roll.modifier_step' => ['required', 'integer', 'min:1', 'max:20'],

            'skills.points' => ['required', 'integer', 'min:0', 'max:100'],
            'skills.items' => ['required', 'array', 'min:0', 'max:40'],
            'skills.items.*.label' => ['required', 'string', 'max:80'],
            'skills.items.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:60'],
            'skills.items.*.default' => ['required', 'boolean'],
            'skills.items.*.subskills' => ['nullable', 'array', 'max:20'],
            'skills.items.*.subskills.*.label' => ['required', 'string', 'max:80'],
            'skills.items.*.subskills.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:60'],
            'skills.items.*.subskills.*.default' => ['required', 'boolean'],

            'extra_fields' => ['nullable', 'array', 'max:40'],
            'extra_fields.*.label' => ['required', 'string', 'max:80'],
            'extra_fields.*.key' => ['required', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:60'],
            'extra_fields.*.type' => ['required', 'in:text,textarea,number'],
            'extra_fields.*.required' => ['boolean'],
            'extra_fields.*.default' => ['nullable'],
            'extra_fields.*.min' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'extra_fields.*.max' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'extra_fields.*.max_length' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'extra_fields.*.points_pool' => ['nullable', 'string', 'regex:/^[a-z][a-z0-9_]*$/', 'max:60'],
            'points' => ['nullable', 'array'],
            'points.*' => ['integer', 'min:0', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $attributes = collect($this->input('attributes.items', []))->values();
        $skills = collect($this->input('skills.items', []))->values();
        $extraFields = collect($this->input('extra_fields', []))->values();

        $attributeKeys = [];
        $attributes = $attributes->map(function (array $item, int $index) use (&$attributeKeys) {
            $item['key'] = $this->resolveUniqueKey(
                $item['key'] ?? null,
                $item['label'] ?? null,
                'attribute',
                $attributeKeys,
                $index + 1,
            );

            return $item;
        });

        $skillKeys = [];
        $skills = $skills->map(function (array $skill, int $index) use (&$skillKeys) {
            $skill['key'] = $this->resolveUniqueKey(
                $skill['key'] ?? null,
                $skill['label'] ?? null,
                'skill',
                $skillKeys,
                $index + 1,
            );

            $skill['subskills'] = collect($skill['subskills'] ?? [])
                ->values()
                ->map(function (array $subskill, int $subIndex) use (&$skillKeys, $skill) {
                    $subskill['key'] = $this->resolveUniqueKey(
                        $subskill['key'] ?? null,
                        trim(($skill['label'] ?? $skill['key']).' '.($subskill['label'] ?? '')),
                        $skill['key'].'_subskill',
                        $skillKeys,
                        $subIndex + 1,
                    );

                    return $subskill;
                })
                ->all();

            return $skill;
        });

        $extraFieldKeys = [];
        $extraFields = $extraFields->map(function (array $field, int $index) use (&$extraFieldKeys) {
            $field['key'] = $this->resolveUniqueKey(
                $field['key'] ?? null,
                $field['label'] ?? null,
                'field',
                $extraFieldKeys,
                $index + 1,
            );

            return $field;
        });

        $this->merge([
            'attributes' => [
                'points' => $this->input('attributes.points', 0),
                'items' => $attributes->all(),
            ],
            'skills' => [
                'points' => 0,
                'items' => $skills->all(),
            ],
            'extra_fields' => $extraFields->all(),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateUniqueKeys($validator, 'attributes.items');
            $this->validateUniqueKeys($validator, 'skills.items');
            $this->validateUniqueSkillKeys($validator);
            $this->validateUniqueKeys($validator, 'extra_fields');

            foreach ($this->input('skills.items', []) as $index => $skill) {
                $this->validateUniqueKeys($validator, "skills.items.$index.subskills");
            }

            $this->validateRanges($validator, 'attributes.items');
            $this->validateRanges($validator, 'extra_fields');
        });
    }

    public function template(): array
    {
        $validated = $this->validated();

        return [
            'attributes' => [
                'points' => (int) $validated['attributes']['points'],
                'items' => collect($validated['attributes']['items'])->map(fn (array $item) => [
                    'key' => $item['key'],
                    'label' => $item['label'],
                    'default' => (int) $item['default'],
                    'min' => $item['min'] ?? null,
                    'max' => $item['max'] ?? null,
                    'roll' => [
                        'enabled' => (bool) ($item['roll']['enabled'] ?? false),
                        'dice' => $item['roll']['dice'],
                        'modifier_step' => (int) $item['roll']['modifier_step'],
                    ],
                ])->values()->all(),
            ],
            'skills' => [
                'points' => 0,
                'items' => collect($validated['skills']['items'] ?? [])->map(fn (array $item) => [
                    'key' => $item['key'],
                    'label' => $item['label'],
                    'default' => (bool) $item['default'],
                    'subskills' => collect($item['subskills'] ?? [])->map(fn (array $subskill) => [
                        'key' => $subskill['key'],
                        'label' => $subskill['label'],
                        'default' => (bool) $subskill['default'],
                    ])->values()->all(),
                ])->values()->all(),
            ],
            'points' => $validated['points'] ?? [],
            'extra_fields' => collect($validated['extra_fields'] ?? [])->map(fn (array $item) => [
                'key' => $item['key'],
                'label' => $item['label'],
                'type' => $item['type'],
                'required' => (bool) ($item['required'] ?? false),
                'default' => $item['type'] === 'number'
                    ? (int) ($item['default'] ?? 0)
                    : ($item['default'] ?? ''),
                'min' => $item['min'] ?? null,
                'max' => $item['max'] ?? null,
                'max_length' => $item['max_length'] ?? 255,
                'points_pool' => $item['points_pool'] ?? null,
            ])->values()->all(),
        ];
    }

    protected function validateUniqueKeys(Validator $validator, string $path): void
    {
        $items = data_get($this->all(), $path, []);
        $keys = collect($items)->pluck('key')->filter();

        if ($keys->count() !== $keys->unique()->count()) {
            $validator->errors()->add($path, 'Keys must be unique.');
        }
    }

    protected function validateUniqueSkillKeys(Validator $validator): void
    {
        $keys = collect($this->input('skills.items', []))
            ->flatMap(fn (array $skill) => [$skill['key'] ?? null, ...collect($skill['subskills'] ?? [])->pluck('key')->all()])
            ->filter();

        if ($keys->count() !== $keys->unique()->count()) {
            $validator->errors()->add('skills.items', 'Skill and subskill keys must be unique.');
        }
    }

    protected function validateRanges(Validator $validator, string $path): void
    {
        foreach (data_get($this->all(), $path, []) as $index => $item) {
            $min = $item['min'] ?? null;
            $max = $item['max'] ?? null;
            $default = $item['default'] ?? null;

            if ($min !== null && $max !== null && (int) $min > (int) $max) {
                $validator->errors()->add("$path.$index.min", 'Min must not be greater than max.');
            }

            if ($default !== null && $min !== null && (int) $default < (int) $min) {
                $validator->errors()->add("$path.$index.default", 'Default must be within min and max.');
            }

            if ($default !== null && $max !== null && (int) $default > (int) $max) {
                $validator->errors()->add("$path.$index.default", 'Default must be within min and max.');
            }
        }
    }

    protected function resolveUniqueKey(?string $existingKey, ?string $label, string $prefix, array &$usedKeys, int $fallbackIndex): string
    {
        $candidate = $existingKey ?: $this->slugify($label ?? '');
        $candidate = $candidate !== '' ? $candidate : $prefix.'_'.$fallbackIndex;
        $base = $candidate;
        $suffix = 2;

        while (in_array($candidate, $usedKeys, true)) {
            $candidate = $base.'_'.$suffix;
            $suffix += 1;
        }

        $usedKeys[] = $candidate;

        return $candidate;
    }

    protected function slugify(string $value): string
    {
        $value = mb_strtolower($value);
        $value = strtr($value, $this->transliterationMap);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';

        return trim($value, '_');
    }
}
