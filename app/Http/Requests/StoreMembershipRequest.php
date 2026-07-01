<?php

namespace App\Http\Requests;

use App\Enums\MembershipRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMembershipRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by policies at the controller layer.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Memberships are normally created by accepting an invitation; these rules
     * cover direct creation by a workspace owner/admin.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'role' => ['required', Rule::enum(MembershipRole::class)->except([MembershipRole::Owner])],
        ];
    }
}
