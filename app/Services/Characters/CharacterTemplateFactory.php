<?php

namespace App\Services\Characters;

class CharacterTemplateFactory
{
    public static function default(): array
    {
        return [
            'attributes' => [
                'points' => 0,
                'items' => [],
            ],
            'skills' => [
                'points' => 0,
                'items' => [],
            ],
            'points' => [],
            'extra_fields' => [],
        ];
    }
}