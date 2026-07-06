<?php

namespace App\Actions\Activities;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Comment;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RecordActivity
{
    /**
     * Write a single immutable activity row.
     *
     * A human-readable label for the subject is snapshotted into `data` at
     * write time, so the rendered feed stays correct even after the subject
     * itself is later hard-deleted (e.g. a removed membership).
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Workspace $workspace, ?User $causer, ActivityType $type, Model $subject, array $data = []): Activity
    {
        $activity = new Activity([
            'workspace_id' => $workspace->id,
            'user_id' => $causer?->id,
            'type' => $type,
            'data' => ['subject_label' => $this->labelFor($subject), ...$data],
        ]);

        $activity->subject()->associate($subject);
        $activity->save();

        return $activity;
    }

    private function labelFor(Model $subject): string
    {
        return match (true) {
            $subject instanceof Workspace => $subject->name,
            $subject instanceof Board => $subject->name,
            $subject instanceof BoardColumn => $subject->name,
            $subject instanceof Task => $subject->title,
            $subject instanceof Comment => Str::limit($subject->body, 60),
            $subject instanceof Membership => $subject->user->name,
            $subject instanceof Invitation => $subject->email,
            default => class_basename($subject),
        };
    }
}
