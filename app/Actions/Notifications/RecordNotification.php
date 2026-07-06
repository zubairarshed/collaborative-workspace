<?php

namespace App\Actions\Notifications;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class RecordNotification
{
    /**
     * Write a single notification for a recipient, unless an unread one for
     * the same (recipient, type, subject) already exists — per UC-09,
     * duplicate notifications should be avoided.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $recipient, NotificationType $type, Model $subject, array $data = []): ?Notification
    {
        $alreadyNotified = Notification::query()
            ->where('user_id', $recipient->id)
            ->where('type', $type)
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey())
            ->whereNull('read_at')
            ->exists();

        if ($alreadyNotified) {
            return null;
        }

        $notification = new Notification([
            'user_id' => $recipient->id,
            'type' => $type,
            'data' => $data,
        ]);

        $notification->subject()->associate($subject);
        $notification->save();

        return $notification;
    }
}
