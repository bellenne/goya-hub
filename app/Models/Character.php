<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Character extends Model
{
    protected $fillable = [
        'game_id',
        'user_id',
        'name',
        'avatar_path',
        'origin',
        'notes',
        'attribute_values',
        'skill_values',
        'extra_field_values',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'attribute_values' => 'array',
            'skill_values' => 'array',
            'extra_field_values' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(CharacterInventoryItem::class);
    }

    public function speakingSceneStates(): MorphMany
    {
        return $this->morphMany(SessionSceneState::class, 'speaker');
    }
}
