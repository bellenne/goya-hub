<?php

namespace App\Models;

use App\Services\Characters\CharacterTemplateFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Game extends Model
{
    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'character_template',
    ];

    protected function casts(): array
    {
        return [
            'character_template' => 'array',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(GameMember::class);
    }

    public function invites(): HasMany
    {
        return $this->hasMany(GameInvite::class);
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class);
    }

    public function npcs(): HasMany
    {
        return $this->hasMany(Npc::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function backgrounds(): HasMany
    {
        return $this->hasMany(Background::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function resolvedCharacterTemplate(): array
    {
        return $this->character_template ?? CharacterTemplateFactory::default();
    }
}
