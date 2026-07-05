<?php

namespace App\Events\Tasks;

use App\Models\BoardColumn;
use App\Models\Task;
use App\Models\User;

final class TaskMoved
{
    public function __construct(
        public readonly Task $task,
        public readonly User $actor,
        public readonly BoardColumn $from,
        public readonly BoardColumn $to,
    ) {}
}
