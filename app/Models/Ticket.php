<?php

namespace App\Models;

use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ticket extends Model
{
    protected $fillable = [
        'game_id',
        'creator_user_id',
        'title',
        'status',
        'last_message_at',
        'last_message_user_id',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'last_message_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function lastMessageUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_message_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(TicketMessage::class)->latestOfMany();
    }
}
