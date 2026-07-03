<?php

namespace App\Actions\Tasks;

use App\Models\Task;

class ArchiveTask
{
    /**
     * Archive or restore a task without deleting it.
     */
    public function handle(Task $task, bool $archived = true): Task
    {
        $task->update(['is_archived' => $archived]);

        return $task;
    }
}
