<?php

namespace App\Listeners\Workspaces;

use App\Actions\Activities\RecordActivity;
use App\Enums\ActivityType;
use App\Events\Workspaces\WorkspaceCreated;
use App\Events\Workspaces\WorkspaceDeleted;
use App\Events\Workspaces\WorkspaceUpdated;

class LogWorkspaceActivity
{
    public function __construct(private readonly RecordActivity $recordActivity) {}

    public function handleCreated(WorkspaceCreated $event): void
    {
        $this->recordActivity->handle($event->workspace, $event->actor, ActivityType::WorkspaceCreated, $event->workspace);
    }

    public function handleUpdated(WorkspaceUpdated $event): void
    {
        $this->recordActivity->handle($event->workspace, $event->actor, ActivityType::WorkspaceUpdated, $event->workspace, [
            'fields' => $event->changedFields,
        ]);
    }

    public function handleDeleted(WorkspaceDeleted $event): void
    {
        $this->recordActivity->handle($event->workspace, $event->actor, ActivityType::WorkspaceDeleted, $event->workspace);
    }
}
