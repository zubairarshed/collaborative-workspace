<?php

use App\Enums\MembershipRole;
use App\Enums\NotificationType;
use App\Models\Board;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;

test('assigning a task notifies the assignee but not on self-assignment', function () {
    $owner = User::factory()->create();
    $assignee = User::factory()->create(['name' => 'Priya Shah']);
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $assignee, MembershipRole::Member);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create(['title' => 'Ship the release']);

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
            'assignee_id' => $assignee->id,
            'version' => $task->version,
        ])
        ->assertRedirect();

    $notification = Notification::query()
        ->where('user_id', $assignee->id)
        ->where('type', NotificationType::TaskAssigned)
        ->firstOrFail();

    expect($notification->data['task_title'])->toBe('Ship the release')
        ->and($notification->message)->toContain($owner->name)
        ->and($notification->message)->toContain('Ship the release');

    // Self-assignment must not notify.
    $secondTask = Task::factory()->forColumn($column, $owner)->create();
    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $secondTask]), [
            'assignee_id' => $owner->id,
            'version' => $secondTask->version,
        ])
        ->assertRedirect();

    expect(Notification::query()->where('user_id', $owner->id)->where('type', NotificationType::TaskAssigned)->exists())->toBeFalse();
});

test('reassigning to the same person twice does not duplicate an unread notification', function () {
    $owner = User::factory()->create();
    $assignee = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $assignee, MembershipRole::Member);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();

    $this->actingAs($owner)->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
        'assignee_id' => $assignee->id,
        'version' => $task->version,
    ])->assertRedirect();

    $this->actingAs($owner)->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
        'title' => 'Retitled, still assigned',
        'assignee_id' => $assignee->id,
        'version' => $task->fresh()->version,
    ])->assertRedirect();

    expect(Notification::query()->where('user_id', $assignee->id)->where('type', NotificationType::TaskAssigned)->count())->toBe(1);
});

test('commenting notifies the task assignee, and mentions notify mentioned members', function () {
    $owner = User::factory()->create();
    $assignee = User::factory()->create(['name' => 'Priya Shah']);
    $mentioned = User::factory()->create(['name' => 'Sam Lee']);
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $assignee, MembershipRole::Member);
    addWorkspaceMember($workspace, $mentioned, MembershipRole::Member);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create(['assignee_id' => $assignee->id]);

    $this->actingAs($owner)
        ->post(route('workspaces.boards.tasks.comments.store', [$workspace, $board, $task]), [
            'body' => 'Hey @Sam can you take a look?',
        ])
        ->assertRedirect();

    expect(Notification::query()->where('user_id', $assignee->id)->where('type', NotificationType::CommentAdded)->exists())->toBeTrue()
        ->and(Notification::query()->where('user_id', $mentioned->id)->where('type', NotificationType::CommentMention)->exists())->toBeTrue()
        ->and(Notification::query()->where('user_id', $owner->id)->exists())->toBeFalse();
});

test('commenting as the assignee does not notify yourself', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create(['assignee_id' => $owner->id]);

    $this->actingAs($owner)
        ->post(route('workspaces.boards.tasks.comments.store', [$workspace, $board, $task]), [
            'body' => 'Noting progress here.',
        ])
        ->assertRedirect();

    expect(Notification::query()->where('user_id', $owner->id)->exists())->toBeFalse();
});

test('inviting an existing user notifies them, inviting an unregistered email does not error', function () {
    $owner = User::factory()->create();
    $existingInvitee = User::factory()->create(['email' => 'known@example.com']);
    $workspace = createWorkspaceFor($owner);

    $this->actingAs($owner)
        ->post(route('workspaces.invitations.store', $workspace), [
            'email' => 'known@example.com',
            'role' => MembershipRole::Member->value,
        ])
        ->assertRedirect();

    expect(Notification::query()->where('user_id', $existingInvitee->id)->where('type', NotificationType::InvitationReceived)->exists())->toBeTrue();

    $this->actingAs($owner)
        ->post(route('workspaces.invitations.store', $workspace), [
            'email' => 'nobody-yet@example.com',
            'role' => MembershipRole::Member->value,
        ])
        ->assertRedirect();
});

test('a user can mark their own notification read, and mark all read', function () {
    $user = User::factory()->create();
    $first = Notification::factory()->create(['user_id' => $user->id]);
    $second = Notification::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->patch(route('notifications.markRead', $first))
        ->assertRedirect();

    expect($first->fresh()->read_at)->not->toBeNull()
        ->and($second->fresh()->read_at)->toBeNull();

    $this->actingAs($user)
        ->patch(route('notifications.read-all'))
        ->assertRedirect();

    expect($second->fresh()->read_at)->not->toBeNull();
});

test('a user cannot mark another user\'s notification as read', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $notification = Notification::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($stranger)
        ->patch(route('notifications.markRead', $notification))
        ->assertForbidden();
});

test('the notifications index only shows the current user\'s notifications', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    Notification::factory()->create(['user_id' => $user->id]);
    Notification::factory()->create(['user_id' => $other->id]);

    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Notifications')
            ->has('notifications.data', 1));
});
