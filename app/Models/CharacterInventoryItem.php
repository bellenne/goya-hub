<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CharacterInventoryItem extends Model
{
    protected $fillable = [
        'character_id',
        'item_id',
        'custom_name',
        'custom_description',
        'custom_image_path',
        'quantity',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function resolvedName(): string
    {
        return $this->item?->name ?? $this->custom_name ?? 'Unknown item';
    }

    public function resolvedDescription(): ?string
    {
        return $this->item?->description ?? $this->custom_description;
    }

    public function resolvedImagePath(): ?string
    {
        return $this->item?->image_path ?? $this->custom_image_path;
    }
}
