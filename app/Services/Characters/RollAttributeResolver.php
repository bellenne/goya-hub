<?php

namespace App\Services\Characters;

class RollAttributeResolver
{
    public function rollableAttributes(array $template): array
    {
        return collect($template['attributes']['items'] ?? [])
            ->filter(fn (array $item) => (bool) data_get($item, 'roll.enabled', false))
            ->map(fn (array $item) => [
                'key' => $item['key'],
                'label' => $item['label'],
                'default' => (int) ($item['default'] ?? 0),
                'dice' => data_get($item, 'roll.dice'),
            ])
            ->values()
            ->all();
    }

    public function findRollableAttribute(array $template, string $key): ?array
    {
        return collect($this->rollableAttributes($template))
            ->firstWhere('key', $key);
    }

    public function modifier(array $attribute, mixed $value): int
    {
        return (int) ($value ?? $attribute['default'] ?? 0);
    }
}
