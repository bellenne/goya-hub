<?php

namespace App\Models;

use App\Enums\SessionMusicPlaybackStatus;
use App\Enums\SessionMusicSourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionMusicState extends Model
{
    protected $fillable = [
        'game_session_id',
        'source_type',
        'title',
        'file_path',
        'direct_url',
        'youtube_url',
        'playback_status',
        'position_seconds',
        'started_at',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => SessionMusicSourceType::class,
            'playback_status' => SessionMusicPlaybackStatus::class,
            'position_seconds' => 'integer',
            'started_at' => 'datetime',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }
}
