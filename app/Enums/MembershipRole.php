<?php

namespace App\Enums;

enum MembershipRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Viewer = 'viewer';

    /**
     * Can edit workspace settings (name, description).
     */
    public function canUpdateWorkspace(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    /**
     * Can delete the workspace. Owner only.
     */
    public function canDeleteWorkspace(): bool
    {
        return $this === self::Owner;
    }

    /**
     * Can invite, remove, and change roles of members.
     */
    public function canManageMembers(): bool
    {
        return in_array($this, [self::Owner, self::Admin], true);
    }

    /**
     * Can create and edit content (boards, tasks, comments).
     */
    public function canContribute(): bool
    {
        return in_array($this, [self::Owner, self::Admin, self::Member], true);
    }
}
