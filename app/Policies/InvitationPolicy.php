<?php

namespace App\Policies;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;

class InvitationPolicy
{
    /**
     * Owners and admins can list a workspace's invitations.
     */
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return (bool) $user->roleIn($workspace)?->canManageMembers();
    }

    /**
     * Owners and admins can view an invitation.
     */
    public function view(User $user, Invitation $invitation): bool
    {
        return (bool) $user->roleIn($invitation->workspace)?->canManageMembers();
    }

    /**
     * Owners and admins can invite members to the workspace.
     */
    public function create(User $user, Workspace $workspace): bool
    {
        return (bool) $user->roleIn($workspace)?->canManageMembers();
    }

    /**
     * Owners and admins can update a pending invitation.
     */
    public function update(User $user, Invitation $invitation): bool
    {
        return (bool) $user->roleIn($invitation->workspace)?->canManageMembers();
    }

    /**
     * Owners and admins can cancel (delete) an invitation.
     */
    public function delete(User $user, Invitation $invitation): bool
    {
        return (bool) $user->roleIn($invitation->workspace)?->canManageMembers();
    }
}
