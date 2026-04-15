<?php

namespace App\Http\Requests;

use App\Models\CharacterInventoryItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCharacterInventoryItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var CharacterInventoryItem $inventoryItem */
        $inventoryItem = $this->route('inventoryItem');

        return $this->user() !== null && $this->user()->can('manageInventory', $inventoryItem->character);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
