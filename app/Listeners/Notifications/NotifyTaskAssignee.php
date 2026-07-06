<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\RecordNotification;
use App\Enums\NotificationType;
use App\Events\Tasks\TaskAssigned;

class NotifyTaskAssignee
{
    public function __construct(private readonly RecordNotification $recordNotification) {}

    public function handleTaskAssigned(TaskAssigned $event): void
    {
        if ($event->assignee === null || $event->assignee->is($event->actor)) {
            return;
        }

        $this->recordNotification->handle($event->assignee, NotificationType::TaskAssigned, $event->task, [
            'actor_name' => $event->actor->name,
            'task_title' => $event->task->title,
        ]);
    }
}
