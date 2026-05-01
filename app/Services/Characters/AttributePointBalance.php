<?php

namespace App\Services\Characters;

class AttributePointBalance
{
    /**
     * @param  array<string, mixed>  $values
     * @param  array<int, array<string, mixed>>  $items
     * @return array{base: int, gained: int, spent: int, available: int}
     */
    public static function calculate(array $values, array $items, int $basePoints): array
    {
        $gained = 0;
        $spent = 0;

        foreach ($items as $item) {
            $key = $item['key'] ?? null;

            if ($key === null) {
                continue;
            }

            $default = (int) ($item['default'] ?? 0);
            $value = (int) ($values[$key] ?? $default);
            $delta = $value - $default;

            if ($delta > 0) {
                $spent += $delta;
            } elseif ($delta < 0) {
                $gained += abs($delta);
            }
        }

        return [
            'base' => $basePoints,
            'gained' => $gained,
            'spent' => $spent,
            'available' => $basePoints + $gained - $spent,
        ];
    }
}
