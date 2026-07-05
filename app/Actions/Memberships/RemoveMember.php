<?php

namespace App\Actions\Memberships;

use App\Events\Memberships\MemberRemoved;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class RemoveMember
{
    /**
     * Remove a member from their workspace.
     *
     * The workspace owner cannot be removed; ownership must be transferred or
     * the workspace deleted instead.
     *
     * @throws ValidationException
     */
    public function handle(Membership $membership, User $actor): void
    {
        if ($membership->user_id === $membership->workspace->owner_id) {
            throw ValidationException::withMessages([
                'membership' => 'The workspace owner cannot be removed.',
            ]);
        }

        $membership->loadMissing('user');
        $membership->delete();

        event(new MemberRemoved($membership, $actor));
    }
}
