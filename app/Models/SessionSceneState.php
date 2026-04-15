<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SessionSceneState extends Model
{
    protected $fillable = [
        'game_session_id',
        'background_id',
        'speaker_type',
        'speaker_id',
        'speaker_scene_npc_id',
        'hidden_character_ids',
    ];

    protected $casts = [
        'hidden_character_ids' => 'array',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function background(): BelongsTo
    {
        return $this->belongsTo(Background::class);
    }

    public function visibleNpcs(): BelongsToMany
    {
        return $this->belongsToMany(Npc::class, 'session_scene_state_npcs')
            ->withPivot(['is_encountered', 'is_present', 'quantity'])
            ->withTimestamps();
    }

    public function sceneNpcs(): HasMany
    {
        return $this->hasMany(SessionSceneNpc::class, 'session_scene_state_id');
    }

    public function speaker(): MorphTo
    {
        return $this->morphTo();
    }
}
