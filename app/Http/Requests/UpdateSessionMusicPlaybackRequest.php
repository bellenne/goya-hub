<?php

namespace App\Http\Requests;

use App\Enums\SessionMusicPlaybackStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionMusicPlaybackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'playback_status' => ['required', Rule::enum(SessionMusicPlaybackStatus::class)],
            'position_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ];
    }
}
