<?php

namespace App\Actions\Comments;

use App\Events\Comments\CommentAdded;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;

class CreateComment
{
    /**
     * Add a comment to a task.
     */
    public function handle(Task $task, User $author, string $body): Comment
    {
        $comment = $task->comments()->create([
            'user_id' => $author->id,
            'body' => $body,
        ]);

        event(new CommentAdded($comment, $task, $author));

        return $comment;
    }
}
