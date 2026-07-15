<?php

use App\Enums\MembershipRole;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owners admins and members can create update reorder and delete columns', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();

    if ($role === MembershipRole::Owner) {
        $actor = $owner;
    } else {
        addWorkspaceMember($workspace, $actor, $role);
    }

    $this->actingAs($actor)
        ->post(route('workspaces.boards.columns.store', [$workspace, $board]), [
            'name' => 'Blocked',
            'wip_limit' => 5,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    $newColumn = BoardColumn::query()
        ->where('board_id', $board->id)
        ->where('name', 'Blocked')
        ->first();

    expect($newColumn)->not->toBeNull()
        ->and($newColumn->wip_limit)->toBe(5);

    $this->actingAs($actor)
        ->patch(route('workspaces.boards.columns.update', [$workspace, $board, $newColumn]), [
            'name' => 'Blocked QA',
            'wip_limit' => 3,
            'version' => $newColumn->version,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    expect($newColumn->fresh()->name)->toBe('Blocked QA')
        ->and($newColumn->fresh()->wip_limit)->toBe(3);

    $orderedIds = $board->columns()->orderBy('position')->pluck('id')->values()->all();
    $last = array_pop($orderedIds);
    array_unshift($orderedIds, $last);

    $this->actingAs($actor)
        ->patch(route('workspaces.boards.columns.reorder', [$workspace, $board]), [
            'columns' => $orderedIds,
            'version' => $board->fresh()->version,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    $positions = $board->columns()->orderBy('position')->pluck('id')->values()->all();
    expect($positions)->toBe($orderedIds);

    $this->actingAs($actor)
        ->delete(route('workspaces.boards.columns.destroy', [$workspace, $board, $newColumn]))
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    $this->assertDatabaseMissing('board_columns', ['id' => $newColumn->id]);
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
    'member' => MembershipRole::Member,
]);

test('viewers cannot create update reorder or delete columns', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();
    addWorkspaceMember($workspace, $viewer, MembershipRole::Viewer);

    $existingColumn = $board->columns()->firstOrFail();

    $this->actingAs($viewer)
        ->post(route('workspaces.boards.columns.store', [$workspace, $board]), [
            'name' => 'Viewer Column',
        ])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->patch(route('workspaces.boards.columns.update', [$workspace, $board, $existingColumn]), [
            'name' => 'Viewer Update',
            'version' => $existingColumn->version,
        ])
        ->assertForbidden();

    $orderedIds = $board->columns()->orderBy('position')->pluck('id')->values()->all();

    $this->actingAs($viewer)
        ->patch(route('workspaces.boards.columns.reorder', [$workspace, $board]), [
            'columns' => array_reverse($orderedIds),
            'version' => $board->version,
        ])
        ->assertForbidden();

    $this->actingAs($viewer)
        ->delete(route('workspaces.boards.columns.destroy', [$workspace, $board, $existingColumn]))
        ->assertForbidden();
});

test('cannot create duplicate column names on the same board', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();

    $this->actingAs($owner)
        ->post(route('workspaces.boards.columns.store', [$workspace, $board]), [
            'name' => 'Todo',
        ])
        ->assertSessionHasErrors('name');
});

test('cannot delete the last remaining column', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->create();
    $column = BoardColumn::factory()->create([
        'board_id' => $board->id,
        'name' => 'Only column',
        'position' => 0,
    ]);

    $this->actingAs($owner)
        ->delete(route('workspaces.boards.columns.destroy', [$workspace, $board, $column]))
        ->assertSessionHasErrors('column');

    $this->assertDatabaseHas('board_columns', ['id' => $column->id]);
});

test('column positions are compacted after deleting one', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create();

    $middle = $board->columns()->where('position', 1)->firstOrFail();

    $this->actingAs($owner)
        ->delete(route('workspaces.boards.columns.destroy', [$workspace, $board, $middle]))
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    $positions = $board->columns()->orderBy('position')->pluck('position')->values()->all();
    expect($positions)->toBe([0, 1, 2]);
});
