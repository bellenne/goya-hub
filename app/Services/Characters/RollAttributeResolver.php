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
                'modifier_step' => max(1, (int) data_get($item, 'roll.modifier_step', 1)),
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
        $step = max(1, (int) ($attribute['modifier_step'] ?? 1));
        $default = (int) ($attribute['default'] ?? 0);
        $current = (int) ($value ?? $default);

        return intdiv($current - $default, $step);
    }
}
