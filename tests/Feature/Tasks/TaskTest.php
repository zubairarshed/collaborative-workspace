<?php

use App\Enums\MembershipRole;
use App\Enums\TaskPriority;
use App\Models\Board;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('owners admins and members can create update move archive and delete tasks', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->where('position', 0)->firstOrFail();
    $otherColumn = $board->columns()->where('position', 1)->firstOrFail();

    if ($role === MembershipRole::Owner) {
        $actor = $owner;
    } else {
        addWorkspaceMember($workspace, $actor, $role);
    }

    $this->actingAs($actor)
        ->post(route('workspaces.boards.columns.tasks.store', [$workspace, $board, $column]), [
            'title' => 'Write release notes',
            'description' => 'Cover the sprint highlights',
            'priority' => TaskPriority::High->value,
            'due_at' => '2026-07-15',
            'assignee_id' => $actor->id,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    $task = Task::query()
        ->where('board_column_id', $column->id)
        ->where('title', 'Write release notes')
        ->first();

    expect($task)->not->toBeNull()
        ->and($task->created_by)->toBe($actor->id)
        ->and($task->priority)->toBe(TaskPriority::High)
        ->and($task->assignee_id)->toBe($actor->id)
        ->and($task->position)->toBe(0);

    $this->actingAs($actor)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
            'title' => 'Write launch notes',
            'description' => 'Updated copy',
            'priority' => TaskPriority::Urgent->value,
            'assignee_id' => null,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    $task->refresh();

    expect($task->title)->toBe('Write launch notes')
        ->and($task->description)->toBe('Updated copy')
        ->and($task->priority)->toBe(TaskPriority::Urgent)
        ->and($task->assignee_id)->toBeNull();

    $secondTask = Task::factory()->forColumn($column, $actor)->create([
        'title' => 'Second task',
        'position' => 1,
    ]);

    $this->actingAs($actor)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $secondTask]), [
            'board_column_id' => $column->id,
            'position' => 0,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    expect($column->tasks()->orderBy('position')->pluck('id')->all())
        ->toBe([$secondTask->id, $task->id]);

    $this->actingAs($actor)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $task]), [
            'board_column_id' => $otherColumn->id,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    expect($task->fresh()->board_column_id)->toBe($otherColumn->id)
        ->and($otherColumn->tasks()->orderBy('position')->pluck('id')->all())
        ->toBe([$task->id]);

    $this->actingAs($actor)
        ->patch(route('workspaces.boards.tasks.archive', [$workspace, $board, $task]), [
            'archived' => true,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    expect($task->fresh()->is_archived)->toBeTrue();

    $this->actingAs($actor)
        ->patch(route('workspaces.boards.tasks.archive', [$workspace, $board, $task]), [
            'archived' => false,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    expect($task->fresh()->is_archived)->toBeFalse();

    $this->actingAs($actor)
        ->delete(route('workspaces.boards.tasks.destroy', [$workspace, $board, $secondTask]))
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    $this->assertSoftDeleted('tasks', ['id' => $secondTask->id]);
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
    'member' => MembershipRole::Member,
]);

test('viewers cannot create update move archive or delete tasks', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();
    addWorkspaceMember($workspace, $viewer, MembershipRole::Viewer);

    $this->actingAs($viewer)
        ->post(route('workspaces.boards.columns.tasks.store', [$workspace, $board, $column]), [
            'title' => 'Viewer task',
        ])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
            'title' => 'Viewer update',
        ])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $task]), [
            'board_column_id' => $column->id,
            'position' => 0,
        ])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->patch(route('workspaces.boards.tasks.archive', [$workspace, $board, $task]), [
            'archived' => true,
        ])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->delete(route('workspaces.boards.tasks.destroy', [$workspace, $board, $task]))
        ->assertForbidden();
});

test('assignee must be a workspace member', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();

    $this->actingAs($owner)
        ->post(route('workspaces.boards.columns.tasks.store', [$workspace, $board, $column]), [
            'title' => 'Assigned task',
            'assignee_id' => $stranger->id,
        ])
        ->assertSessionHasErrors('assignee_id');

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
            'assignee_id' => $stranger->id,
        ])
        ->assertSessionHasErrors('assignee_id');
});

test('cannot create a task in a column from another board', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $otherBoard = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $foreignColumn = $otherBoard->columns()->firstOrFail();

    $this->actingAs($owner)
        ->post(route('workspaces.boards.columns.tasks.store', [$workspace, $board, $foreignColumn]), [
            'title' => 'Wrong column',
        ])
        ->assertNotFound();
});

test('cannot move a task to a column on another board', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $otherBoard = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $foreignColumn = $otherBoard->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $task]), [
            'board_column_id' => $foreignColumn->id,
        ])
        ->assertSessionHasErrors('board_column_id');
});

test('cannot update a task through the wrong board route', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $otherBoard = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $otherBoard, $task]), [
            'title' => 'Cross-board update',
        ])
        ->assertNotFound();
});

test('task positions are compacted after deleting one', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();

    $first = Task::factory()->forColumn($column, $owner)->create(['position' => 0]);
    $middle = Task::factory()->forColumn($column, $owner)->create(['position' => 1]);
    $last = Task::factory()->forColumn($column, $owner)->create(['position' => 2]);

    $this->actingAs($owner)
        ->delete(route('workspaces.boards.tasks.destroy', [$workspace, $board, $middle]))
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    $positions = $column->tasks()->orderBy('position')->pluck('position')->values()->all();
    expect($positions)->toBe([0, 1]);

    $remainingIds = $column->tasks()->orderBy('position')->pluck('id')->all();
    expect($remainingIds)->toBe([$first->id, $last->id]);
});

test('board show includes active tasks and hides archived ones', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();

    $activeTask = Task::factory()->forColumn($column, $owner)->create([
        'title' => 'Active task',
        'position' => 0,
    ]);
    Task::factory()->forColumn($column, $owner)->archived()->create([
        'title' => 'Archived task',
        'position' => 1,
    ]);

    $this->actingAs($owner)
        ->get(route('workspaces.boards.show', [$workspace, $board]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('boards/Show')
            ->has('columns', 4)
            ->has('members')
            ->where('columns.0.tasks.0.id', $activeTask->id)
            ->where('columns.0.tasks.0.title', 'Active task')
            ->where('columns.0.can.createTask', true)
            ->where('columns.0.tasks.0.can.update', true)
            ->missing('columns.0.tasks.1')
        );
});

test('viewers can view tasks on the board page but cannot create them', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create(['title' => 'Visible task']);
    addWorkspaceMember($workspace, $viewer, MembershipRole::Viewer);

    $this->actingAs($viewer)
        ->get(route('workspaces.boards.show', [$workspace, $board]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('boards/Show')
            ->where('columns.0.tasks.0.id', $task->id)
            ->where('columns.0.can.createTask', false)
            ->where('columns.0.tasks.0.can.update', false)
            ->where('columns.0.tasks.0.can.move', false)
            ->where('columns.0.tasks.0.can.delete', false)
        );
});
