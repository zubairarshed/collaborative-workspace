<?php

namespace App\Enums;

use App\Models\Notification;

enum NotificationType: string
{
    case TaskAssigned = 'task.assigned';
    case CommentAdded = 'comment.added';
    case CommentMention = 'comment.mentioned';
    case InvitationReceived = 'invitation.received';

    /**
     * Render a first-person, human-readable sentence for this notification.
     */
    public function message(Notification $notification): string
    {
        $data = $notification->data ?? [];
        $actor = $data['actor_name'] ?? 'Someone';

        return match ($this) {
            self::TaskAssigned => "{$actor} assigned you to \"{$data['task_title']}\".",
            self::CommentAdded => "{$actor} commented on \"{$data['task_title']}\": \"{$data['comment_snippet']}\"",
            self::CommentMention => "{$actor} mentioned you in a comment on \"{$data['task_title']}\": \"{$data['comment_snippet']}\"",
            self::InvitationReceived => "{$actor} invited you to join \"{$data['workspace_name']}\".",
        };
    }
}
