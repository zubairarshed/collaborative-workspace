<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;

class MembershipPolicy
{
    /**
     * Any member of the workspace can view its member list.
     */
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $user->belongsToWorkspace($workspace);
    }

    /**
     * Any member of the workspace can view a membership.
     */
    public function view(User $user, Membership $membership): bool
    {
        return $user->belongsToWorkspace($membership->workspace);
    }

    /**
     * Owners and admins can add members directly.
     */
    public function create(User $user, Workspace $workspace): bool
    {
        return (bool) $user->roleIn($workspace)?->canManageMembers();
    }

    /**
     * Owners and admins can change a member's role.
     */
    public function update(User $user, Membership $membership): bool
    {
        return (bool) $user->roleIn($membership->workspace)?->canManageMembers();
    }

    /**
     * Owners and admins can remove members; any member may remove themselves
     * (leave), except the workspace owner.
     */
    public function delete(User $user, Membership $membership): bool
    {
        $isOwnerOfWorkspace = $membership->user_id === $membership->workspace->owner_id;

        if ($isOwnerOfWorkspace) {
            return false;
        }

        if ($membership->user_id === $user->id) {
            return true;
        }

        return (bool) $user->roleIn($membership->workspace)?->canManageMembers();
    }
}
