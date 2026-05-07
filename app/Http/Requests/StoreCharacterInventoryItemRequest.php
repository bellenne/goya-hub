<?php

namespace App\Http\Requests;

use App\Models\Character;
use App\Models\Item;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCharacterInventoryItemRequest extends FormRequest
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
            'item_id' => ['nullable', 'integer', 'exists:items,id'],
            'custom_name' => ['nullable', 'string', 'max:255'],
            'custom_description' => ['nullable', 'string', 'max:5000'],
            'custom_image' => ['nullable', 'image', 'max:51200'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        /** @var Character $character */
        $character = $this->route('character');

        $validator->after(function (Validator $validator) use ($character) {
            $itemId = $this->input('item_id');
            $customName = $this->input('custom_name');

            if (! $itemId && ! filled($customName)) {
                $validator->errors()->add('custom_name', 'Select a catalog item or provide a custom item name.');
            }

            if ($itemId) {
                $item = Item::query()->find($itemId);

                if ($item === null || $item->game_id !== $character->game_id) {
                    $validator->errors()->add('item_id', 'Selected item does not belong to this game.');
                }
            }
        });
    }
}
