<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionSceneNpc extends Model
{
    protected $table = 'session_scene_state_npcs';

    protected $fillable = [
        'session_scene_state_id',
        'npc_id',
        'scene_type',
        'display_name',
        'is_group',
        'group_size',
        'is_encountered',
        'is_present',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'is_group' => 'boolean',
            'is_encountered' => 'boolean',
            'is_present' => 'boolean',
            'group_size' => 'integer',
            'quantity' => 'integer',
        ];
    }

    public function sceneState(): BelongsTo
    {
        return $this->belongsTo(SessionSceneState::class, 'session_scene_state_id');
    }

    public function npc(): BelongsTo
    {
        return $this->belongsTo(Npc::class);
    }
}
