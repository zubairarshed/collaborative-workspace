<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\RecordNotification;
use App\Enums\NotificationType;
use App\Events\Memberships\MemberInvited;
use App\Models\User;

class NotifyInvitee
{
    public function __construct(private readonly RecordNotification $recordNotification) {}

    /**
     * Notify the invited email's owner, if they already have an account.
     */
    public function handleMemberInvited(MemberInvited $event): void
    {
        $invitee = User::query()->where('email', $event->invitation->email)->first();

        if ($invitee === null) {
            return;
        }

        $this->recordNotification->handle($invitee, NotificationType::InvitationReceived, $event->invitation, [
            'actor_name' => $event->actor->name,
            'workspace_name' => $event->invitation->workspace->name,
        ]);
    }
}
