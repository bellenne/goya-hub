<?php

namespace App\Models;

use App\Enums\GameRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMember extends Model
{
    protected $fillable = [
        'game_id',
        'user_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'role' => GameRole::class,
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
}
