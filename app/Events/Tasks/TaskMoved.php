<?php

namespace App\Events\Tasks;

use App\Models\BoardColumn;
use App\Models\Task;
use App\Models\User;

final class TaskMoved
{
    /**
     * The task's version after the move (ADR-004); realtime clients
     * reconcile their local copy against it.
     */
    public readonly int $version;

    public function __construct(
        public readonly Task $task,
        public readonly User $actor,
        public readonly BoardColumn $from,
        public readonly BoardColumn $to,
    ) {
        $this->version = $task->currentVersion();
    }
}
