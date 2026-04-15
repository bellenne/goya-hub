<?php

namespace App\Models;

use App\Enums\DiceType;
use App\Enums\DiceRollSourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionDiceRoll extends Model
{
    protected $fillable = [
        'game_session_id',
        'user_id',
        'source_type',
        'source_id',
        'source_name',
        'dice_count',
        'dice_type',
        'modifier',
        'manual_modifier',
        'attribute_key',
        'attribute_label',
        'attribute_modifier',
        'roll_values',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'dice_type' => DiceType::class,
            'source_type' => DiceRollSourceType::class,
            'roll_values' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'game_session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
