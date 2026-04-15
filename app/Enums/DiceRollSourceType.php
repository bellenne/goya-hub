<?php

namespace App\Enums;

enum DiceRollSourceType: string
{
    case Character = 'character';
    case SceneNpc = 'scene_npc';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
