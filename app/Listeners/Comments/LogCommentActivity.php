<?php

namespace App\Listeners\Comments;

use App\Actions\Activities\RecordActivity;
use App\Enums\ActivityType;
use App\Events\Comments\CommentAdded;

class LogCommentActivity
{
    public function __construct(private readonly RecordActivity $recordActivity) {}

    public function handleCommentAdded(CommentAdded $event): void
    {
        $this->recordActivity->handle($event->task->board->workspace, $event->actor, ActivityType::CommentAdded, $event->comment, [
            'task_title' => $event->task->title,
        ]);
    }
}
