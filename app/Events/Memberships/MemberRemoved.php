<?php

namespace App\Events\Memberships;

use App\Models\Membership;
use App\Models\User;

final class MemberRemoved
{
    public function __construct(
        public readonly Membership $membership,
        public readonly User $actor,
    ) {}
}
