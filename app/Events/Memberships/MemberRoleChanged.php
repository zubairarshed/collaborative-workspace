<?php

namespace App\Events\Memberships;

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\User;

final class MemberRoleChanged
{
    public function __construct(
        public readonly Membership $membership,
        public readonly User $actor,
        public readonly MembershipRole $oldRole,
        public readonly MembershipRole $newRole,
    ) {}
}
