<?php

use App\Enums\ActivityType;
use App\Enums\MembershipRole;
use App\Models\Activity;
use App\Models\Board;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;

test('owners admins and members can comment on a task', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();

    if ($role === MembershipRole::Owner) {
        $actor = $owner;
    } else {
        addWorkspaceMember($workspace, $actor, $role);
    }

    $this->actingAs($actor)
        ->post(route('workspaces.boards.tasks.comments.store', [$workspace, $board, $task]), [
            'body' => 'Looks good to me.',
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    $comment = Comment::query()->where('task_id', $task->id)->firstOrFail();

    expect($comment->body)->toBe('Looks good to me.')
        ->and($comment->user_id)->toBe($actor->id);

    $activity = Activity::query()->where('type', ActivityType::CommentAdded)->where('subject_id', $comment->id)->firstOrFail();
    expect($activity->workspace_id)->toBe($workspace->id)
        ->and($activity->data['task_title'])->toBe($task->title);
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
    'member' => MembershipRole::Member,
]);

test('viewers cannot comment on a task', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();
    addWorkspaceMember($workspace, $viewer, MembershipRole::Viewer);

    $this->actingAs($viewer)
        ->post(route('workspaces.boards.tasks.comments.store', [$workspace, $board, $task]), [
            'body' => 'Trying to comment.',
        ])
        ->assertForbidden();
});

test('a comment cannot be empty', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();

    $this->actingAs($owner)
        ->post(route('workspaces.boards.tasks.comments.store', [$workspace, $board, $task]), [
            'body' => '',
        ])
        ->assertSessionHasErrors('body');
});

test('board show includes task comments', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();
    Comment::factory()->forTask($task, $owner)->create(['body' => 'First comment']);

    $this->actingAs($owner)
        ->get(route('workspaces.boards.show', [$workspace, $board]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('boards/Show')
            ->where('columns.0.tasks.0.comments.0.body', 'First comment')
            ->where('columns.0.tasks.0.comments.0.author.id', $owner->id));
});
