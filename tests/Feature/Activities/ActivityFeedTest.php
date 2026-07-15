<?php

use App\Enums\ActivityType;
use App\Enums\MembershipRole;
use App\Models\Activity;
use App\Models\Board;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('creating a workspace logs an activity', function () {
    $owner = User::factory()->create();

    $this->actingAs($owner)
        ->post(route('workspaces.store'), ['name' => 'Acme Inc'])
        ->assertRedirect();

    $activity = Activity::query()->where('type', ActivityType::WorkspaceCreated)->firstOrFail();

    expect($activity->user_id)->toBe($owner->id)
        ->and($activity->message)->toContain($owner->name)
        ->and($activity->message)->toContain('Acme Inc');
});

test('creating a board and a task logs activities', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    $this->actingAs($owner)
        ->post(route('workspaces.boards.store', $workspace), ['name' => 'Launch Board'])
        ->assertRedirect();

    $board = Board::query()->where('workspace_id', $workspace->id)->where('name', 'Launch Board')->firstOrFail();
    expect(Activity::query()->where('type', ActivityType::BoardCreated)->where('workspace_id', $workspace->id)->exists())->toBeTrue();

    $column = $board->columns()->firstOrFail();

    $this->actingAs($owner)
        ->post(route('workspaces.boards.columns.tasks.store', [$workspace, $board, $column]), ['title' => 'Ship it'])
        ->assertRedirect();

    $task = Task::query()->where('title', 'Ship it')->firstOrFail();

    $activity = Activity::query()->where('type', ActivityType::TaskCreated)->where('subject_id', $task->id)->firstOrFail();
    expect($activity->workspace_id)->toBe($workspace->id);
});

test('moving a task to another column logs a TaskMoved activity with column names', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->where('position', 0)->firstOrFail();
    $otherColumn = $board->columns()->where('position', 1)->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $task]), [
            'board_column_id' => $otherColumn->id,
            'version' => $task->version,
        ])
        ->assertRedirect();

    $activity = Activity::query()->where('type', ActivityType::TaskMoved)->where('subject_id', $task->id)->firstOrFail();

    expect($activity->data['from_column'])->toBe($column->name)
        ->and($activity->data['to_column'])->toBe($otherColumn->name)
        ->and($activity->message)->toContain($column->name)
        ->and($activity->message)->toContain($otherColumn->name);
});

test('reordering a task within the same column does not log a move activity', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $first = Task::factory()->forColumn($column, $owner)->create(['position' => 0]);
    Task::factory()->forColumn($column, $owner)->create(['position' => 1]);

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $first]), [
            'board_column_id' => $column->id,
            'position' => 1,
            'version' => $first->version,
        ])
        ->assertRedirect();

    expect(Activity::query()->where('type', ActivityType::TaskMoved)->exists())->toBeFalse();
});

test('assigning a task logs a TaskAssigned activity separate from field updates', function () {
    $owner = User::factory()->create();
    $assignee = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $assignee, MembershipRole::Member);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
            'title' => 'Renamed task',
            'assignee_id' => $assignee->id,
            'version' => $task->version,
        ])
        ->assertRedirect();

    $assigned = Activity::query()->where('type', ActivityType::TaskAssigned)->where('subject_id', $task->id)->firstOrFail();
    expect($assigned->data['assignee_name'])->toBe($assignee->name);

    $updated = Activity::query()->where('type', ActivityType::TaskUpdated)->where('subject_id', $task->id)->firstOrFail();
    expect($updated->data['fields'])->toBe(['title']);
});

test('inviting, accepting, and removing a member each log an activity', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    $this->actingAs($owner)
        ->post(route('workspaces.invitations.store', $workspace), [
            'email' => $invitee->email,
            'role' => MembershipRole::Member->value,
        ])
        ->assertRedirect();

    $invitation = $workspace->invitations()->where('email', $invitee->email)->firstOrFail();
    expect(Activity::query()->where('type', ActivityType::MemberInvited)->where('subject_id', $invitation->id)->exists())->toBeTrue();

    $this->actingAs($invitee)
        ->post(route('invitations.accept', $invitation->token))
        ->assertRedirect();

    $membership = $workspace->memberships()->where('user_id', $invitee->id)->firstOrFail();
    $joined = Activity::query()->where('type', ActivityType::MemberJoined)->where('subject_id', $membership->id)->firstOrFail();
    expect($joined->user_id)->toBe($invitee->id);

    $this->actingAs($owner)
        ->delete(route('memberships.destroy', $membership))
        ->assertRedirect();

    expect(Activity::query()->where('type', ActivityType::MemberRemoved)->where('subject_id', $membership->id)->exists())->toBeTrue();
});

test('only workspace members can view the activity feed', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    $this->actingAs($stranger)
        ->get(route('workspaces.activity', $workspace))
        ->assertForbidden();

    $this->actingAs($owner)
        ->get(route('workspaces.activity', $workspace))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Activity')
            ->has('activities.data')
        );
});

test('the activity feed paginates and orders newest first', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    foreach (range(1, 25) as $i) {
        Activity::factory()->create([
            'workspace_id' => $workspace->id,
            'user_id' => $owner->id,
            'created_at' => now()->addSeconds($i),
        ]);
    }

    $this->actingAs($owner)
        ->get(route('workspaces.activity', $workspace))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Activity')
            ->has('activities.data', 20)
            ->where('activities.total', 25)
        );
});
