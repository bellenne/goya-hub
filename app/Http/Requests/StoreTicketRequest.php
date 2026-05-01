<?php

namespace App\Http\Requests;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Game $game */
        $game = $this->route('game');

        return $this->user() !== null && $this->user()->can('viewTickets', $game);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:10000'],
        ];
    }
}
