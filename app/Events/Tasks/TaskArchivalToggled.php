<?php

namespace App\Events\Tasks;

use App\Models\Task;
use App\Models\User;

final class TaskArchivalToggled
{
    public function __construct(
        public readonly Task $task,
        public readonly User $actor,
        public readonly bool $archived,
    ) {}
}
