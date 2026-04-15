<?php

namespace App\Models;

use App\Enums\NpcType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Npc extends Model
{
    protected $fillable = [
        'game_id',
        'name',
        'avatar_path',
        'type',
        'description',
        'uses_character_sheet',
        'attribute_values',
        'skill_values',
        'extra_field_values',
        'stats',
    ];

    protected function casts(): array
    {
        return [
            'type' => NpcType::class,
            'uses_character_sheet' => 'boolean',
            'attribute_values' => 'array',
            'skill_values' => 'array',
            'extra_field_values' => 'array',
            'stats' => 'array',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function sceneStates(): BelongsToMany
    {
        return $this->belongsToMany(SessionSceneState::class, 'session_scene_state_npcs')
            ->withPivot(['is_encountered', 'is_present', 'quantity'])
            ->withTimestamps();
    }
}
