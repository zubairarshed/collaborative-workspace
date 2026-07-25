<?php

namespace App\Events\Tasks;

use App\Models\Task;
use App\Models\User;

final class TaskUpdated
{
    /**
     * The task's version after the update (ADR-004); realtime clients
     * reconcile their local copy against it.
     */
    public readonly int $version;

    /**
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public readonly Task $task,
        public readonly User $actor,
        public readonly array $changedFields,
    ) {
        $this->version = $task->currentVersion();
    }
}
