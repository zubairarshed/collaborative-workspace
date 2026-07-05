<?php

namespace App\Listeners\Tasks;

use App\Actions\Activities\RecordActivity;
use App\Enums\ActivityType;
use App\Events\Tasks\TaskArchivalToggled;
use App\Events\Tasks\TaskAssigned;
use App\Events\Tasks\TaskCreated;
use App\Events\Tasks\TaskDeleted;
use App\Events\Tasks\TaskMoved;
use App\Events\Tasks\TaskUpdated;

class LogTaskActivity
{
    public function __construct(private readonly RecordActivity $recordActivity) {}

    public function handleCreated(TaskCreated $event): void
    {
        $this->recordActivity->handle($event->task->board->workspace, $event->actor, ActivityType::TaskCreated, $event->task);
    }

    public function handleUpdated(TaskUpdated $event): void
    {
        $this->recordActivity->handle($event->task->board->workspace, $event->actor, ActivityType::TaskUpdated, $event->task, [
            'fields' => $event->changedFields,
        ]);
    }

    public function handleAssigned(TaskAssigned $event): void
    {
        $this->recordActivity->handle($event->task->board->workspace, $event->actor, ActivityType::TaskAssigned, $event->task, [
            'assignee_name' => $event->assignee?->name,
        ]);
    }

    public function handleMoved(TaskMoved $event): void
    {
        $this->recordActivity->handle($event->task->board->workspace, $event->actor, ActivityType::TaskMoved, $event->task, [
            'from_column' => $event->from->name,
            'to_column' => $event->to->name,
        ]);
    }

    public function handleArchivalToggled(TaskArchivalToggled $event): void
    {
        $this->recordActivity->handle($event->task->board->workspace, $event->actor, ActivityType::TaskArchivalToggled, $event->task, [
            'archived' => $event->archived,
        ]);
    }

    public function handleDeleted(TaskDeleted $event): void
    {
        $this->recordActivity->handle($event->task->board->workspace, $event->actor, ActivityType::TaskDeleted, $event->task);
    }
}
