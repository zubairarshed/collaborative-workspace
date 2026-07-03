<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\User;

class BoardColumnPolicy
{
    /**
     * Any member of the workspace can list a board's columns.
     */
    public function viewAny(User $user, Board $board): bool
    {
        return $user->belongsToWorkspace($board->workspace);
    }

    /**
     * Any member of the workspace can view a column.
     */
    public function view(User $user, BoardColumn $column): bool
    {
        return $user->belongsToWorkspace($column->board->workspace);
    }

    /**
     * Owners, admins, and members can add columns.
     */
    public function create(User $user, Board $board): bool
    {
        return (bool) $user->roleIn($board->workspace)?->canContribute();
    }

    /**
     * Owners, admins, and members can update a column.
     */
    public function update(User $user, BoardColumn $column): bool
    {
        return (bool) $user->roleIn($column->board->workspace)?->canContribute();
    }

    /**
     * Owners, admins, and members can delete a column.
     */
    public function delete(User $user, BoardColumn $column): bool
    {
        return (bool) $user->roleIn($column->board->workspace)?->canContribute();
    }

    /**
     * Owners, admins, and members can reorder columns.
     */
    public function reorder(User $user, Board $board): bool
    {
        return (bool) $user->roleIn($board->workspace)?->canContribute();
    }
}
