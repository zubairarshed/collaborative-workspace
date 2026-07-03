<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Any member of the workspace can list a board's tasks.
     */
    public function viewAny(User $user, Board $board): bool
    {
        return $user->belongsToWorkspace($board->workspace);
    }

    /**
     * Any member of the workspace can view a task.
     */
    public function view(User $user, Task $task): bool
    {
        return $user->belongsToWorkspace($task->board->workspace);
    }

    /**
     * Owners, admins, and members can create tasks.
     */
    public function create(User $user, BoardColumn $column): bool
    {
        return (bool) $user->roleIn($column->board->workspace)?->canContribute();
    }

    /**
     * Owners, admins, and members can update a task.
     */
    public function update(User $user, Task $task): bool
    {
        return (bool) $user->roleIn($task->board->workspace)?->canContribute();
    }

    /**
     * Owners, admins, and members can move a task between columns.
     */
    public function move(User $user, Task $task): bool
    {
        return (bool) $user->roleIn($task->board->workspace)?->canContribute();
    }

    /**
     * Owners, admins, and members can archive or restore a task.
     */
    public function archive(User $user, Task $task): bool
    {
        return (bool) $user->roleIn($task->board->workspace)?->canContribute();
    }

    /**
     * Owners, admins, and members can soft-delete a task.
     */
    public function delete(User $user, Task $task): bool
    {
        return (bool) $user->roleIn($task->board->workspace)?->canContribute();
    }

    /**
     * Owners, admins, and members can restore a soft-deleted task.
     */
    public function restore(User $user, Task $task): bool
    {
        return (bool) $user->roleIn($task->board->workspace)?->canContribute();
    }

    /**
     * Only the workspace owner can permanently delete a task.
     */
    public function forceDelete(User $user, Task $task): bool
    {
        return (bool) $user->roleIn($task->board->workspace)?->canDeleteWorkspace();
    }
}
