<?php

namespace App\Events\Memberships;

use App\Models\Membership;

final class MemberJoined
{
    public function __construct(
        public readonly Membership $membership,
    ) {}
}
