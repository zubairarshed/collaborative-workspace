<?php

namespace App\Actions\Memberships;

use App\Enums\MembershipRole;
use App\Models\Membership;
use Illuminate\Validation\ValidationException;

class UpdateMembershipRole
{
    /**
     * Change a member's role within their workspace.
     *
     * The workspace owner's role cannot be changed here, and Owner cannot be
     * assigned through this action (ownership transfer is a separate concern).
     *
     * @throws ValidationException
     */
    public function handle(Membership $membership, MembershipRole $role): Membership
    {
        if ($membership->user_id === $membership->workspace->owner_id) {
            throw ValidationException::withMessages([
                'role' => "The workspace owner's role cannot be changed.",
            ]);
        }

        if ($role === MembershipRole::Owner) {
            throw ValidationException::withMessages([
                'role' => 'Ownership cannot be assigned by changing a role.',
            ]);
        }

        $membership->update(['role' => $role]);

        return $membership;
    }
}
