<?php

namespace App\Listeners\Memberships;

use App\Actions\Activities\RecordActivity;
use App\Enums\ActivityType;
use App\Events\Memberships\InvitationCancelled;
use App\Events\Memberships\MemberInvited;
use App\Events\Memberships\MemberJoined;
use App\Events\Memberships\MemberRemoved;
use App\Events\Memberships\MemberRoleChanged;

class LogMembershipActivity
{
    public function __construct(private readonly RecordActivity $recordActivity) {}

    public function handleMemberInvited(MemberInvited $event): void
    {
        $this->recordActivity->handle($event->invitation->workspace, $event->actor, ActivityType::MemberInvited, $event->invitation, [
            'role' => $event->invitation->role->value,
        ]);
    }

    public function handleInvitationCancelled(InvitationCancelled $event): void
    {
        $this->recordActivity->handle($event->invitation->workspace, $event->actor, ActivityType::InvitationCancelled, $event->invitation);
    }

    public function handleMemberJoined(MemberJoined $event): void
    {
        $this->recordActivity->handle($event->membership->workspace, $event->membership->user, ActivityType::MemberJoined, $event->membership);
    }

    public function handleMemberRemoved(MemberRemoved $event): void
    {
        $this->recordActivity->handle($event->membership->workspace, $event->actor, ActivityType::MemberRemoved, $event->membership);
    }

    public function handleMemberRoleChanged(MemberRoleChanged $event): void
    {
        $this->recordActivity->handle($event->membership->workspace, $event->actor, ActivityType::MemberRoleChanged, $event->membership, [
            'old_role' => $event->oldRole->value,
            'new_role' => $event->newRole->value,
        ]);
    }
}
