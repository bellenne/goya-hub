<?php

namespace App\Http\Requests;

use App\Enums\NpcType;
use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNpcTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Game $game */
        $game = $this->route('game');

        return $this->user() !== null && $this->user()->can('manageContent', $game);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(NpcType::class)],
            'back_to_session_id' => ['nullable', 'integer'],
        ];
    }
}
