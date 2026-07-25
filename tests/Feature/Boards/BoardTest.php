<?php

use App\Enums\MembershipRole;
use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('owners admins and members can create boards and default columns', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    if ($role === MembershipRole::Owner) {
        $actor = $owner;
    } else {
        addWorkspaceMember($workspace, $actor, $role);
    }

    $this->actingAs($actor)
        ->post(route('workspaces.boards.store', $workspace), [
            'name' => 'Sprint Board',
            'description' => 'Work for this sprint',
        ])
        ->assertRedirect();

    $board = Board::query()
        ->where('workspace_id', $workspace->id)
        ->where('name', 'Sprint Board')
        ->first();

    expect($board)->not->toBeNull()
        ->and($board->created_by)->toBe($actor->id)
        ->and($board->is_archived)->toBeFalse();

    expect($board?->columns()->count())->toBe(4);
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
    'member' => MembershipRole::Member,
]);

test('viewers cannot create boards', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $viewer, MembershipRole::Viewer);

    $this->actingAs($viewer)
        ->post(route('workspaces.boards.store', $workspace), [
            'name' => 'Blocked Board',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('boards', [
        'workspace_id' => $workspace->id,
        'name' => 'Blocked Board',
    ]);
});

test('owners admins and members can update boards', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create([
        'name' => 'Initial Name',
    ]);

    if ($role === MembershipRole::Owner) {
        $actor = $owner;
    } else {
        addWorkspaceMember($workspace, $actor, $role);
    }

    $this->actingAs($actor)
        ->patch(route('workspaces.boards.update', [$workspace, $board]), [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'version' => $board->version,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    $board->refresh();

    expect($board->name)->toBe('Updated Name')
        ->and($board->description)->toBe('Updated description');
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
    'member' => MembershipRole::Member,
]);

test('viewers cannot update boards', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create([
        'name' => 'Original Name',
    ]);
    addWorkspaceMember($workspace, $viewer, MembershipRole::Viewer);

    $this->actingAs($viewer)
        ->patch(route('workspaces.boards.update', [$workspace, $board]), [
            'name' => 'Viewer Update',
            'version' => $board->version,
        ])
        ->assertForbidden();

    expect($board->fresh()->name)->toBe('Original Name');
});

test('owners and admins can archive and restore boards', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create([
        'is_archived' => false,
    ]);

    if ($role === MembershipRole::Owner) {
        $actor = $owner;
    } else {
        addWorkspaceMember($workspace, $actor, $role);
    }

    $this->actingAs($actor)
        ->patch(route('workspaces.boards.archive', [$workspace, $board]), [
            'archived' => true,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    expect($board->fresh()->is_archived)->toBeTrue();

    $this->actingAs($actor)
        ->patch(route('workspaces.boards.archive', [$workspace, $board]), [
            'archived' => false,
        ])
        ->assertRedirect(route('workspaces.boards.show', [$workspace, $board]));

    expect($board->fresh()->is_archived)->toBeFalse();
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
]);

test('members and viewers cannot archive boards', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create([
        'is_archived' => false,
    ]);
    addWorkspaceMember($workspace, $actor, $role);

    $this->actingAs($actor)
        ->patch(route('workspaces.boards.archive', [$workspace, $board]), [
            'archived' => true,
        ])
        ->assertForbidden();

    expect($board->fresh()->is_archived)->toBeFalse();
})->with([
    'member' => MembershipRole::Member,
    'viewer' => MembershipRole::Viewer,
]);

test('owners and admins can delete boards', function (MembershipRole $role) {
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
        ->delete(route('workspaces.boards.destroy', [$workspace, $board]))
        ->assertRedirect(route('workspaces.show', $workspace));

    expect($board->fresh()->trashed())->toBeTrue();
    $this->assertSoftDeleted('boards', ['id' => $board->id]);
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
]);

test('workspace members can view boards and outsiders cannot', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $stranger = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $board = Board::factory()->forWorkspace($workspace, $owner)->withDefaultColumns()->create([
        'name' => 'Roadmap Board',
    ]);
    addWorkspaceMember($workspace, $member, MembershipRole::Member);

    $this->actingAs($member)
        ->get(route('workspaces.boards.show', [$workspace, $board]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('boards/Show')
            ->where('board.id', $board->id)
            ->where('board.name', 'Roadmap Board')
            ->has('columns', 4)
        );

    $this->actingAs($stranger)
        ->get(route('workspaces.boards.show', [$workspace, $board]))
        ->assertForbidden();
});

test('workspace show includes boards list', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $first = Board::factory()->forWorkspace($workspace, $owner)->create([
        'name' => 'First Board',
        'slug' => 'first-board',
        'position' => 0,
    ]);
    $second = Board::factory()->forWorkspace($workspace, $owner)->archived()->create([
        'name' => 'Second Board',
        'slug' => 'second-board',
        'position' => 1,
    ]);

    $this->actingAs($owner)
        ->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Show')
            ->has('boards', 2)
            ->where('boards.0.id', $first->id)
            ->where('boards.1.id', $second->id)
            ->where('boards.1.is_archived', true)
        );
});
