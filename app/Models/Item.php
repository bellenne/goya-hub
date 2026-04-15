<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    protected $fillable = [
        'game_id',
        'name',
        'image_path',
        'category',
        'description',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(CharacterInventoryItem::class);
    }
}
