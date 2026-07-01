<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    /**
     * Any authenticated user can list their own workspaces.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any member of the workspace can view it.
     */
    public function view(User $user, Workspace $workspace): bool
    {
        return $user->belongsToWorkspace($workspace);
    }

    /**
     * Any authenticated user can create a workspace.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Owners and admins can update workspace settings.
     */
    public function update(User $user, Workspace $workspace): bool
    {
        return (bool) $user->roleIn($workspace)?->canUpdateWorkspace();
    }

    /**
     * Only the owner can delete the workspace.
     */
    public function delete(User $user, Workspace $workspace): bool
    {
        return (bool) $user->roleIn($workspace)?->canDeleteWorkspace();
    }

    /**
     * Only the owner can restore the workspace.
     */
    public function restore(User $user, Workspace $workspace): bool
    {
        return (bool) $user->roleIn($workspace)?->canDeleteWorkspace();
    }

    /**
     * Only the owner can permanently delete the workspace.
     */
    public function forceDelete(User $user, Workspace $workspace): bool
    {
        return (bool) $user->roleIn($workspace)?->canDeleteWorkspace();
    }
}
