<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Background extends Model
{
    protected $fillable = [
        'game_id',
        'title',
        'image_path',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function sceneStates(): HasMany
    {
        return $this->hasMany(SessionSceneState::class);
    }
}
