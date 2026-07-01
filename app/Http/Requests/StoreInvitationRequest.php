<?php

namespace App\Http\Requests;

use App\Enums\MembershipRole;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreInvitationRequest extends FormRequest
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
     * Normalize the email before validation so the uniqueness checks and the
     * stored value stay consistent.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, callable $fail): void {
                    $workspace = $this->route('workspace');

                    if (! $workspace instanceof Workspace) {
                        return;
                    }

                    if ($workspace->members()->where('email', $value)->exists()) {
                        $fail('This person is already a member of the workspace.');

                        return;
                    }

                    if ($workspace->invitations()->pending()->where('email', $value)->exists()) {
                        $fail('A pending invitation already exists for this email.');
                    }
                },
            ],
            'role' => ['required', Rule::enum(MembershipRole::class)->except([MembershipRole::Owner])],
        ];
    }
}
