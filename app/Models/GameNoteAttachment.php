<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameNoteAttachment extends Model
{
    protected $fillable = [
        'game_note_id',
        'image_path',
        'original_name',
        'size',
    ];

    public function note(): BelongsTo
    {
        return $this->belongsTo(GameNote::class, 'game_note_id');
    }
}
