<?php

use App\Enums\ActivityType;
use App\Enums\MembershipRole;
use App\Enums\NotificationType;
use App\Models\Activity;
use App\Models\Board;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('updating a task with the current version succeeds and increments the version', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create(['title' => 'Original title']);

    expect($task->version)->toBe(1);

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
            'title' => 'Updated title',
            'version' => 1,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    $task->refresh();

    expect($task->title)->toBe('Updated title')
        ->and($task->version)->toBe(2);
});

test('updating a task with a stale version returns 409 with fresh state and leaves the task unchanged', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create(['title' => 'First title']);

    // A first client updates the task, bumping the version to 2.
    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
            'title' => 'Second title',
            'version' => 1,
        ])
        ->assertRedirect();

    // A second client still holding version 1 must be rejected.
    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
            'title' => 'Conflicting title',
            'version' => 1,
        ])
        ->assertStatus(409)
        ->assertJsonPath('entity.type', 'Task')
        ->assertJsonPath('entity.id', $task->id)
        ->assertJsonPath('entity.version', 2);

    $task->refresh();

    expect($task->title)->toBe('Second title')
        ->and($task->version)->toBe(2);
});

test('moving a task with a stale version returns 409 and leaves the ordering unchanged', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->where('position', 0)->firstOrFail();
    $otherColumn = $board->columns()->where('position', 1)->firstOrFail();
    $first = Task::factory()->forColumn($column, $owner)->create(['position' => 0]);
    $second = Task::factory()->forColumn($column, $owner)->create(['position' => 1]);

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $first]), [
            'board_column_id' => $otherColumn->id,
            'version' => 99,
        ])
        ->assertStatus(409)
        ->assertJsonPath('entity.type', 'Task')
        ->assertJsonPath('entity.version', 1);

    expect($first->fresh()->board_column_id)->toBe($column->id)
        ->and($column->tasks()->orderBy('position')->pluck('id')->all())
        ->toBe([$first->id, $second->id]);
});

test('the second of two clients moving the same task with the original version gets 409', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $member, MembershipRole::Member);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->where('position', 0)->firstOrFail();
    $otherColumn = $board->columns()->where('position', 1)->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create(['position' => 0]);

    // Both clients rendered the board while the task was at version 1.
    // Client one moves the task first.
    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $task]), [
            'board_column_id' => $otherColumn->id,
            'version' => 1,
        ])
        ->assertRedirect();

    expect($task->fresh()->version)->toBe(2);

    // Client two submits a move based on the pre-move version.
    $this->actingAs($member)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $task]), [
            'board_column_id' => $column->id,
            'version' => 1,
        ])
        ->assertStatus(409)
        ->assertJsonPath('entity.version', 2);

    expect($task->fresh()->board_column_id)->toBe($otherColumn->id);
});

test('reordering columns with a stale board version returns 409 and leaves the order unchanged', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $originalOrder = $board->columns()->orderBy('position')->pluck('id')->all();

    // A first reorder bumps the board version to 2.
    $this->actingAs($owner)
        ->patch(route('workspaces.boards.columns.reorder', [$workspace, $board]), [
            'columns' => array_reverse($originalOrder),
            'version' => 1,
        ])
        ->assertRedirect();

    $reversedOrder = $board->columns()->orderBy('position')->pluck('id')->all();
    expect($reversedOrder)->toBe(array_reverse($originalOrder))
        ->and($board->fresh()->version)->toBe(2);

    // A stale client reordering against version 1 must be rejected.
    $this->actingAs($owner)
        ->patch(route('workspaces.boards.columns.reorder', [$workspace, $board]), [
            'columns' => $originalOrder,
            'version' => 1,
        ])
        ->assertStatus(409)
        ->assertJsonPath('entity.type', 'Board')
        ->assertJsonPath('entity.version', 2);

    expect($board->columns()->orderBy('position')->pluck('id')->all())->toBe($reversedOrder);
});

test('updating a board with a stale version returns 409 and leaves the board unchanged', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create([
        'name' => 'Original Board',
    ]);

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.update', [$workspace, $board]), [
            'name' => 'Renamed Board',
            'version' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.update', [$workspace, $board]), [
            'name' => 'Conflicting Rename',
            'version' => 1,
        ])
        ->assertStatus(409)
        ->assertJsonPath('entity.type', 'Board')
        ->assertJsonPath('entity.version', 2);

    expect($board->fresh()->name)->toBe('Renamed Board');
});

test('updating a column with a stale version returns 409 and leaves the column unchanged', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $originalName = $column->name;

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.columns.update', [$workspace, $board, $column]), [
            'name' => 'Stale Rename',
            'version' => 99,
        ])
        ->assertStatus(409)
        ->assertJsonPath('entity.type', 'BoardColumn')
        ->assertJsonPath('entity.version', 1);

    expect($column->fresh()->name)->toBe($originalName);
});

test('mutating a deleted task returns 404 from route binding, not 409', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();

    $this->actingAs($owner)
        ->delete(route('workspaces.boards.tasks.destroy', [$workspace, $board, $task]))
        ->assertRedirect();

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
            'title' => 'Ghost update',
            'version' => 1,
        ])
        ->assertNotFound();

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $task]), [
            'board_column_id' => $column->id,
            'version' => 1,
        ])
        ->assertNotFound();
});

test('version is required by validation on every versioned mutation', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
            'title' => 'No version supplied',
        ])
        ->assertSessionHasErrors('version');

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $task]), [
            'board_column_id' => $column->id,
        ])
        ->assertSessionHasErrors('version');

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.columns.reorder', [$workspace, $board]), [
            'columns' => $board->columns()->pluck('id')->all(),
        ])
        ->assertSessionHasErrors('version');

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.columns.update', [$workspace, $board, $column]), [
            'name' => 'No version supplied',
        ])
        ->assertSessionHasErrors('version');

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.update', [$workspace, $board]), [
            'name' => 'No version supplied',
        ])
        ->assertSessionHasErrors('version');

    expect($task->fresh()->version)->toBe(1)
        ->and($board->fresh()->version)->toBe(1);
});

test('activity and notification listeners still fire on successful versioned updates', function () {
    $owner = User::factory()->create();
    $assignee = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $assignee, MembershipRole::Member);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    $column = $board->columns()->where('position', 0)->firstOrFail();
    $otherColumn = $board->columns()->where('position', 1)->firstOrFail();
    $task = Task::factory()->forColumn($column, $owner)->create();

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.update', [$workspace, $board, $task]), [
            'title' => 'Versioned rename',
            'assignee_id' => $assignee->id,
            'version' => 1,
        ])
        ->assertRedirect();

    $this->actingAs($owner)
        ->patch(route('workspaces.boards.tasks.move', [$workspace, $board, $task]), [
            'board_column_id' => $otherColumn->id,
            'version' => 2,
        ])
        ->assertRedirect();

    expect(Activity::query()->where('type', ActivityType::TaskUpdated)->where('subject_id', $task->id)->exists())->toBeTrue()
        ->and(Activity::query()->where('type', ActivityType::TaskAssigned)->where('subject_id', $task->id)->exists())->toBeTrue()
        ->and(Activity::query()->where('type', ActivityType::TaskMoved)->where('subject_id', $task->id)->exists())->toBeTrue()
        ->and(Notification::query()->where('user_id', $assignee->id)->where('type', NotificationType::TaskAssigned)->exists())->toBeTrue();

    expect($task->fresh()->version)->toBe(3);
});
