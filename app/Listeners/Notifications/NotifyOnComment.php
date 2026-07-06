<?php

namespace App\Listeners\Notifications;

use App\Actions\Notifications\RecordNotification;
use App\Enums\NotificationType;
use App\Events\Comments\CommentAdded;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotifyOnComment
{
    public function __construct(private readonly RecordNotification $recordNotification) {}

    public function handleCommentAdded(CommentAdded $event): void
    {
        $data = [
            'actor_name' => $event->actor->name,
            'task_title' => $event->task->title,
            'comment_snippet' => Str::limit($event->comment->body, 80),
        ];

        $assignee = $event->task->assignee;

        if ($assignee !== null && $assignee->isNot($event->actor)) {
            $this->recordNotification->handle($assignee, NotificationType::CommentAdded, $event->comment, $data);
        }

        foreach ($this->mentionedMembers($event) as $member) {
            $this->recordNotification->handle($member, NotificationType::CommentMention, $event->comment, $data);
        }
    }

    /**
     * Best-effort @mention parsing: match plain-text "@word" tokens against
     * workspace members' first names, case-insensitively. There is no
     * autocomplete/username system — this is intentionally lightweight and
     * can be ambiguous when multiple members share a first name.
     *
     * @return Collection<int, User>
     */
    private function mentionedMembers(CommentAdded $event): Collection
    {
        preg_match_all('/@(\w+)/', $event->comment->body, $matches);

        $tokens = collect($matches[1])->map(fn (string $token): string => Str::lower($token))->unique();

        if ($tokens->isEmpty()) {
            return collect();
        }

        /** @var Workspace $workspace */
        $workspace = $event->task->board->workspace;

        return $workspace->members()
            ->get()
            ->filter(fn (User $member): bool => $tokens->contains(Str::lower(Str::before($member->name, ' '))))
            ->reject(fn (User $member): bool => $member->is($event->actor))
            ->unique('id')
            ->values();
    }
}
