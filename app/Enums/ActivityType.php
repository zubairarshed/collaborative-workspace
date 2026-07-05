<?php

namespace App\Enums;

use App\Models\Activity;

enum ActivityType: string
{
    case WorkspaceCreated = 'workspace.created';
    case WorkspaceUpdated = 'workspace.updated';
    case WorkspaceDeleted = 'workspace.deleted';

    case BoardCreated = 'board.created';
    case BoardUpdated = 'board.updated';
    case BoardArchivalToggled = 'board.archival_toggled';
    case BoardDeleted = 'board.deleted';

    case ColumnCreated = 'column.created';
    case ColumnUpdated = 'column.updated';
    case ColumnDeleted = 'column.deleted';
    case ColumnsReordered = 'column.reordered';

    case TaskCreated = 'task.created';
    case TaskUpdated = 'task.updated';
    case TaskAssigned = 'task.assigned';
    case TaskMoved = 'task.moved';
    case TaskArchivalToggled = 'task.archival_toggled';
    case TaskDeleted = 'task.deleted';

    case MemberInvited = 'member.invited';
    case InvitationCancelled = 'invitation.cancelled';
    case MemberJoined = 'member.joined';
    case MemberRemoved = 'member.removed';
    case MemberRoleChanged = 'member.role_changed';

    /**
     * Render a human-readable sentence for an activity of this type.
     */
    public function message(Activity $activity): string
    {
        $causer = $activity->causer?->name ?? 'Someone';
        $subject = $activity->data['subject_label'] ?? 'this item';
        $data = $activity->data ?? [];

        return match ($this) {
            self::WorkspaceCreated => "{$causer} created the workspace \"{$subject}\".",
            self::WorkspaceUpdated => "{$causer} updated the workspace ({$this->fieldList($data)}).",
            self::WorkspaceDeleted => "{$causer} deleted the workspace \"{$subject}\".",

            self::BoardCreated => "{$causer} created the board \"{$subject}\".",
            self::BoardUpdated => "{$causer} updated the board \"{$subject}\" ({$this->fieldList($data)}).",
            self::BoardArchivalToggled => ($data['archived'] ?? true)
                ? "{$causer} archived the board \"{$subject}\"."
                : "{$causer} restored the board \"{$subject}\".",
            self::BoardDeleted => "{$causer} deleted the board \"{$subject}\".",

            self::ColumnCreated => "{$causer} added the column \"{$subject}\".",
            self::ColumnUpdated => "{$causer} updated the column \"{$subject}\" ({$this->fieldList($data)}).",
            self::ColumnDeleted => "{$causer} deleted the column \"{$subject}\".",
            self::ColumnsReordered => "{$causer} reordered the board's columns.",

            self::TaskCreated => "{$causer} created the task \"{$subject}\".",
            self::TaskUpdated => "{$causer} updated the task \"{$subject}\" ({$this->fieldList($data)}).",
            self::TaskAssigned => ($data['assignee_name'] ?? null)
                ? "{$causer} assigned \"{$subject}\" to {$data['assignee_name']}."
                : "{$causer} unassigned \"{$subject}\".",
            self::TaskMoved => "{$causer} moved \"{$subject}\" from {$data['from_column']} to {$data['to_column']}.",
            self::TaskArchivalToggled => ($data['archived'] ?? true)
                ? "{$causer} archived the task \"{$subject}\"."
                : "{$causer} restored the task \"{$subject}\".",
            self::TaskDeleted => "{$causer} deleted the task \"{$subject}\".",

            self::MemberInvited => "{$causer} invited {$subject} to the workspace as {$data['role']}.",
            self::InvitationCancelled => "{$causer} cancelled the invitation for {$subject}.",
            self::MemberJoined => "{$subject} joined the workspace.",
            self::MemberRemoved => "{$causer} removed {$subject} from the workspace.",
            self::MemberRoleChanged => "{$causer} changed {$subject}'s role from {$data['old_role']} to {$data['new_role']}.",
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function fieldList(array $data): string
    {
        $fields = $data['fields'] ?? [];

        return $fields === [] ? 'no changes' : implode(', ', $fields);
    }
}
