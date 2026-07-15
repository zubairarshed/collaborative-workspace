<?php

namespace App\Events\Tasks;

use App\Models\Task;
use App\Models\User;

final class TaskAssigned
{
    /**
     * The task's version after the assignment (ADR-004); realtime clients
     * reconcile their local copy against it.
     */
    public readonly int $version;

    public function __construct(
        public readonly Task $task,
        public readonly User $actor,
        public readonly ?User $assignee,
    ) {
        $this->version = $task->currentVersion();
    }
}
