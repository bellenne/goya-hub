<?php

namespace App\Http\Requests;

use App\Models\Character;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCharacterSheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Character $character */
        $character = $this->route('character');

        return $this->user() !== null && $this->user()->can('manageInventory', $character);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'attribute_values' => ['required', 'array'],
            'attribute_values.*' => ['nullable', 'integer'],
            'skill_values' => ['required', 'array'],
            'skill_values.*' => ['nullable', 'boolean'],
            'extra_field_values' => ['nullable', 'array'],
            'extra_field_values.*' => ['nullable'],
            'back_to_session_id' => ['nullable', 'integer'],
        ];
    }
};
