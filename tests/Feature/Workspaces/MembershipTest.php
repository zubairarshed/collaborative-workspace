<?php

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owners and admins can change a members role', function (MembershipRole $actorRole) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $member = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    if ($actorRole === MembershipRole::Owner) {
        $actor = $owner;
    } else {
        addWorkspaceMember($workspace, $actor, $actorRole);
    }

    $membership = addWorkspaceMember($workspace, $member, MembershipRole::Member);

    $this->actingAs($actor)
        ->patch(route('memberships.update', $membership), [
            'role' => MembershipRole::Viewer->value,
        ])
        ->assertRedirect(route('workspaces.show', $workspace));

    expect($membership->fresh()->role)->toBe(MembershipRole::Viewer);
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
]);

test('members and viewers cannot change roles', function (MembershipRole $actorRole) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $member = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $actor, $actorRole);
    $membership = addWorkspaceMember($workspace, $member, MembershipRole::Member);

    $this->actingAs($actor)
        ->patch(route('memberships.update', $membership), [
            'role' => MembershipRole::Viewer->value,
        ])
        ->assertForbidden();

    expect($membership->fresh()->role)->toBe(MembershipRole::Member);
})->with([
    'member' => MembershipRole::Member,
    'viewer' => MembershipRole::Viewer,
]);

test('the workspace owners role cannot be changed', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $ownerMembership = Membership::query()
        ->where('workspace_id', $workspace->id)
        ->where('user_id', $owner->id)
        ->firstOrFail();

    $this->actingAs($owner)
        ->patch(route('memberships.update', $ownerMembership), [
            'role' => MembershipRole::Admin->value,
        ])
        ->assertSessionHasErrors('role');

    expect($ownerMembership->fresh()->role)->toBe(MembershipRole::Owner);
});

test('ownership cannot be assigned through a role change', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $membership = addWorkspaceMember($workspace, $member, MembershipRole::Member);

    $this->actingAs($owner)
        ->patch(route('memberships.update', $membership), [
            'role' => MembershipRole::Owner->value,
        ])
        ->assertSessionHasErrors('role');

    expect($membership->fresh()->role)->toBe(MembershipRole::Member);
});

test('owners and admins can remove members', function (MembershipRole $actorRole) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $member = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    if ($actorRole === MembershipRole::Owner) {
        $actor = $owner;
    } else {
        addWorkspaceMember($workspace, $actor, $actorRole);
    }

    $membership = addWorkspaceMember($workspace, $member, MembershipRole::Member);

    $this->actingAs($actor)
        ->delete(route('memberships.destroy', $membership))
        ->assertRedirect(route('workspaces.show', $workspace));

    $this->assertDatabaseMissing('memberships', ['id' => $membership->id]);
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
]);

test('the workspace owner cannot be removed', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $admin, MembershipRole::Admin);

    $ownerMembership = Membership::query()
        ->where('workspace_id', $workspace->id)
        ->where('user_id', $owner->id)
        ->firstOrFail();

    $this->actingAs($admin)
        ->delete(route('memberships.destroy', $ownerMembership))
        ->assertForbidden();

    $this->assertDatabaseHas('memberships', ['id' => $ownerMembership->id]);
});

test('a non-owner member can leave a workspace', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $membership = addWorkspaceMember($workspace, $member, MembershipRole::Member);

    $this->actingAs($member)
        ->delete(route('memberships.destroy', $membership))
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseMissing('memberships', ['id' => $membership->id]);
});

test('the workspace owner cannot leave via membership deletion', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    $ownerMembership = Membership::query()
        ->where('workspace_id', $workspace->id)
        ->where('user_id', $owner->id)
        ->firstOrFail();

    $this->actingAs($owner)
        ->delete(route('memberships.destroy', $ownerMembership))
        ->assertForbidden();

    $this->assertDatabaseHas('memberships', ['id' => $ownerMembership->id]);
});

test('users cannot manage memberships in workspaces they do not belong to', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    $member = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    $membership = addWorkspaceMember($workspace, $member, MembershipRole::Member);

    $this->actingAs($stranger)
        ->patch(route('memberships.update', $membership), [
            'role' => MembershipRole::Viewer->value,
        ])
        ->assertForbidden();

    $this->actingAs($stranger)
        ->delete(route('memberships.destroy', $membership))
        ->assertForbidden();
});
