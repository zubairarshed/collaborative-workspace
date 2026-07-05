<?php

namespace App\Actions\Tasks;

use App\Events\Tasks\TaskArchivalToggled;
use App\Models\Task;
use App\Models\User;

class ArchiveTask
{
    /**
     * Archive or restore a task without deleting it.
     */
    public function handle(Task $task, User $actor, bool $archived = true): Task
    {
        $task->update(['is_archived' => $archived]);

        event(new TaskArchivalToggled($task, $actor, $archived));

        return $task;
    }
}
