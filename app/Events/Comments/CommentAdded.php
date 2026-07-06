<?php

namespace App\Events\Comments;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;

final class CommentAdded
{
    public function __construct(
        public readonly Comment $comment,
        public readonly Task $task,
        public readonly User $actor,
    ) {}
}
