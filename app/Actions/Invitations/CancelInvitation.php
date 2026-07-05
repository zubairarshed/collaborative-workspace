<?php

namespace App\Actions\Invitations;

use App\Events\Memberships\InvitationCancelled;
use App\Models\Invitation;
use App\Models\User;

class CancelInvitation
{
    /**
     * Cancel (delete) a pending invitation.
     */
    public function handle(Invitation $invitation, User $actor): void
    {
        $invitation->delete();

        event(new InvitationCancelled($invitation, $actor));
    }
}
