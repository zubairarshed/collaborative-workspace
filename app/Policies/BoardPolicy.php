<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;

class BoardPolicy
{
    /**
     * Any member of the workspace can list its boards.
     */
    public function viewAny(User $user, Workspace $workspace): bool
    {
        return $user->belongsToWorkspace($workspace);
    }

    /**
     * Any member of the workspace can view a board.
     */
    public function view(User $user, Board $board): bool
    {
        return $user->belongsToWorkspace($board->workspace);
    }

    /**
     * Owners, admins, and members can create boards.
     */
    public function create(User $user, Workspace $workspace): bool
    {
        return (bool) $user->roleIn($workspace)?->canContribute();
    }

    /**
     * Owners, admins, and members can update board settings.
     */
    public function update(User $user, Board $board): bool
    {
        return (bool) $user->roleIn($board->workspace)?->canContribute();
    }

    /**
     * Owners and admins can archive or restore a board.
     */
    public function archive(User $user, Board $board): bool
    {
        return (bool) $user->roleIn($board->workspace)?->canUpdateWorkspace();
    }

    /**
     * Owners and admins can soft-delete a board.
     */
    public function delete(User $user, Board $board): bool
    {
        return (bool) $user->roleIn($board->workspace)?->canUpdateWorkspace();
    }

    /**
     * Owners and admins can restore a soft-deleted board.
     */
    public function restore(User $user, Board $board): bool
    {
        return (bool) $user->roleIn($board->workspace)?->canUpdateWorkspace();
    }

    /**
     * Only the workspace owner can permanently delete a board.
     */
    public function forceDelete(User $user, Board $board): bool
    {
        return (bool) $user->roleIn($board->workspace)?->canDeleteWorkspace();
    }
}
