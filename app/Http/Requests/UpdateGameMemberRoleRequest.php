<?php

namespace App\Http\Requests;

use App\Enums\GameRole;
use App\Models\GameMember;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGameMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var GameMember $member */
        $member = $this->route('member');

        return $this->user() !== null && $this->user()->can('manageMemberRoles', $member->game);
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in([
                GameRole::CoGm->value,
                GameRole::Player->value,
            ])],
        ];
    }
}
