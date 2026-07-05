<?php

namespace App\Events\Memberships;

use App\Models\Invitation;
use App\Models\User;

final class MemberInvited
{
    public function __construct(
        public readonly Invitation $invitation,
        public readonly User $actor,
    ) {}
}
