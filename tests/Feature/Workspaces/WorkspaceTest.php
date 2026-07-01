<?php

use App\Enums\MembershipRole;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('creating a workspace creates an owner membership', function () {
    $owner = User::factory()->create();

    $workspace = createWorkspaceFor($owner, ['name' => 'Acme Marketing']);

    expect($workspace->owner_id)->toBe($owner->id);

    $membership = Membership::query()
        ->where('workspace_id', $workspace->id)
        ->where('user_id', $owner->id)
        ->first();

    expect($membership)->not->toBeNull()
        ->and($membership->role)->toBe(MembershipRole::Owner);
});

test('dashboard lists workspaces the user belongs to', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $workspace = createWorkspaceFor($user, ['name' => 'My Workspace']);
    createWorkspaceFor($otherUser, ['name' => 'Other Workspace']);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Index')
            ->has('workspaces', 1)
            ->where('workspaces.0.id', $workspace->id)
            ->where('workspaces.0.name', 'My Workspace')
            ->where('workspaces.0.role', MembershipRole::Owner->value)
        );
});

test('dashboard shows pending invitations for the authenticated users email', function () {
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    $invitation = Invitation::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'invitee@example.com',
        'role' => MembershipRole::Member,
    ]);

    $response = $this->actingAs($invitee)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Index')
            ->has('pendingInvitations', 1)
            ->where('pendingInvitations.0.token', $invitation->token)
            ->where('pendingInvitations.0.workspace.name', $workspace->name)
        );
});

test('dashboard does not show pending invitations for other users', function () {
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    Invitation::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'someone-else@example.com',
    ]);

    $response = $this->actingAs($invitee)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Index')
            ->has('pendingInvitations', 0)
        );
});

test('guests cannot access the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('users who are not members cannot view a workspace', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    $this->actingAs($stranger)
        ->get(route('workspaces.show', $workspace))
        ->assertForbidden();
});

test('members can view their workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $member, MembershipRole::Member);

    $this->actingAs($member)
        ->get(route('workspaces.show', $workspace))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Show')
            ->where('workspace.id', $workspace->id)
            ->where('role', MembershipRole::Member->value)
        );
});

test('owners and admins can update a workspace', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    if ($role === MembershipRole::Owner) {
        $actor = $owner;
    } else {
        addWorkspaceMember($workspace, $actor, $role);
    }

    $this->actingAs($actor)
        ->put(route('workspaces.update', $workspace), [
            'name' => 'Updated Name',
            'description' => 'Updated description',
        ])
        ->assertRedirect(route('workspaces.show', $workspace));

    expect($workspace->fresh())
        ->name->toBe('Updated Name')
        ->description->toBe('Updated description');
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
]);

test('members and viewers cannot update a workspace', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $actor, $role);

    $this->actingAs($actor)
        ->put(route('workspaces.update', $workspace), [
            'name' => 'Blocked Update',
        ])
        ->assertForbidden();
})->with([
    'member' => MembershipRole::Member,
    'viewer' => MembershipRole::Viewer,
]);

test('only the owner can delete a workspace', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $admin, MembershipRole::Admin);

    $this->actingAs($admin)
        ->delete(route('workspaces.destroy', $workspace))
        ->assertForbidden();

    $this->actingAs($owner)
        ->delete(route('workspaces.destroy', $workspace))
        ->assertRedirect(route('dashboard'));

    expect(Workspace::withTrashed()->find($workspace->id))->not->toBeNull()
        ->and(Workspace::find($workspace->id))->toBeNull();
});

test('authenticated users can create a workspace', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('workspaces.store'), [
            'name' => 'New Workspace',
            'description' => 'A fresh workspace',
        ])
        ->assertRedirect();

    $workspace = Workspace::query()->where('name', 'New Workspace')->first();

    expect($workspace)->not->toBeNull()
        ->and($workspace->owner_id)->toBe($user->id);

    $this->assertDatabaseHas('memberships', [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => MembershipRole::Owner->value,
    ]);
});
