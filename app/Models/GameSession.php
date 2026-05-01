<?php

namespace App\Models;

use App\Enums\SessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GameSession extends Model
{
    protected $fillable = [
        'game_id',
        'title',
        'invite_code',
        'invite_token',
        'status',
        'started_at',
        'ended_at',
        'status_before_gm_disconnect',
        'gm_grace_started_at',
        'gm_grace_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => SessionStatus::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'gm_grace_started_at' => 'datetime',
            'gm_grace_ends_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(SessionParticipant::class);
    }

    public function sceneState(): HasOne
    {
        return $this->hasOne(SessionSceneState::class, 'game_session_id');
    }

    public function diceRolls(): HasMany
    {
        return $this->hasMany(SessionDiceRoll::class, 'game_session_id');
    }

    public function gmConnections(): HasMany
    {
        return $this->hasMany(GameSessionGmConnection::class);
    }

    public function musicState(): HasOne
    {
        return $this->hasOne(SessionMusicState::class);
    }
}
