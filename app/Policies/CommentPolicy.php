<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class CommentPolicy
{
    /**
     * Owners, admins, and members can comment on a task.
     */
    public function create(User $user, Task $task): bool
    {
        return (bool) $user->roleIn($task->board->workspace)?->canContribute();
    }
}
