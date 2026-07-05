<?php

namespace App\Actions\Invitations;

use App\Events\Memberships\MemberJoined;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AcceptInvitation
{
    /**
     * Accept a pending invitation and create the corresponding membership.
     *
     * Runs in a transaction so the membership and the invitation's
     * accepted_at marker are persisted together.
     *
     * @throws ValidationException
     */
    public function handle(Invitation $invitation, User $user): Membership
    {
        if (! $this->isPending($invitation)) {
            throw ValidationException::withMessages([
                'invitation' => 'This invitation is no longer valid.',
            ]);
        }

        if (Str::lower($user->email) !== Str::lower($invitation->email)) {
            throw ValidationException::withMessages([
                'invitation' => 'This invitation was sent to a different email address.',
            ]);
        }

        $membership = DB::transaction(function () use ($invitation, $user): Membership {
            $membership = Membership::updateOrCreate(
                ['workspace_id' => $invitation->workspace_id, 'user_id' => $user->id],
                ['role' => $invitation->role, 'joined_at' => now()],
            );

            $invitation->forceFill(['accepted_at' => now()])->save();

            return $membership;
        });

        $membership->setRelation('user', $user);
        event(new MemberJoined($membership));

        return $membership;
    }

    private function isPending(Invitation $invitation): bool
    {
        return $invitation->accepted_at === null
            && $invitation->rejected_at === null
            && $invitation->expires_at !== null
            && $invitation->expires_at->isFuture();
    }
}
