<?php

namespace App\Http\Requests;

use App\Models\Game;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpsertBackgroundRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Game $game */
        $game = $this->route('game');

        return $this->user() !== null && $this->user()->can('manageContent', $game);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => [$this->route('background') ? 'nullable' : 'required', 'image'],
            'back_to_session_id' => ['nullable', 'integer'],
            'apply_to_session' => ['nullable', 'boolean'],
        ];
    }
}
