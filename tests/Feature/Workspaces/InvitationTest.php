<?php

use App\Enums\MembershipRole;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('owners and admins can invite members', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    if ($role === MembershipRole::Owner) {
        $actor = $owner;
    } else {
        addWorkspaceMember($workspace, $actor, $role);
    }

    $this->actingAs($actor)
        ->post(route('workspaces.invitations.store', $workspace), [
            'email' => 'new-member@example.com',
            'role' => MembershipRole::Member->value,
        ])
        ->assertRedirect(route('workspaces.show', $workspace));

    $this->assertDatabaseHas('invitations', [
        'workspace_id' => $workspace->id,
        'email' => 'new-member@example.com',
        'role' => MembershipRole::Member->value,
        'invited_by' => $actor->id,
    ]);
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
]);

test('members and viewers cannot invite members', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $actor, $role);

    $this->actingAs($actor)
        ->post(route('workspaces.invitations.store', $workspace), [
            'email' => 'new-member@example.com',
            'role' => MembershipRole::Member->value,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('invitations', [
        'workspace_id' => $workspace->id,
        'email' => 'new-member@example.com',
    ]);
})->with([
    'member' => MembershipRole::Member,
    'viewer' => MembershipRole::Viewer,
]);

test('cannot invite an existing member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create(['email' => 'member@example.com']);
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $member, MembershipRole::Member);

    $this->actingAs($owner)
        ->post(route('workspaces.invitations.store', $workspace), [
            'email' => 'member@example.com',
            'role' => MembershipRole::Admin->value,
        ])
        ->assertSessionHasErrors('email');
});

test('cannot create duplicate pending invitations for the same email', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    Invitation::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'pending@example.com',
    ]);

    $this->actingAs($owner)
        ->post(route('workspaces.invitations.store', $workspace), [
            'email' => 'pending@example.com',
            'role' => MembershipRole::Member->value,
        ])
        ->assertSessionHasErrors('email');
});

test('invited user can accept a pending invitation', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $workspace = createWorkspaceFor($owner);

    $invitation = Invitation::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'invitee@example.com',
        'role' => MembershipRole::Member,
    ]);

    $this->actingAs($invitee)
        ->post(route('invitations.accept', $invitation->token))
        ->assertRedirect(route('workspaces.show', $workspace));

    $this->assertDatabaseHas('memberships', [
        'workspace_id' => $workspace->id,
        'user_id' => $invitee->id,
        'role' => MembershipRole::Member->value,
    ]);

    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

test('user with a different email cannot accept an invitation', function () {
    $owner = User::factory()->create();
    $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
    $workspace = createWorkspaceFor($owner);

    $invitation = Invitation::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'invitee@example.com',
        'role' => MembershipRole::Member,
    ]);

    $this->actingAs($wrongUser)
        ->post(route('invitations.accept', $invitation->token))
        ->assertSessionHasErrors('invitation');

    $this->assertDatabaseMissing('memberships', [
        'workspace_id' => $workspace->id,
        'user_id' => $wrongUser->id,
    ]);
});

test('expired invitations cannot be accepted', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $workspace = createWorkspaceFor($owner);

    $invitation = Invitation::factory()->expired()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'invitee@example.com',
        'role' => MembershipRole::Member,
    ]);

    $this->actingAs($invitee)
        ->post(route('invitations.accept', $invitation->token))
        ->assertSessionHasErrors('invitation');

    $this->assertDatabaseMissing('memberships', [
        'workspace_id' => $workspace->id,
        'user_id' => $invitee->id,
    ]);
});

test('accepted invitations no longer appear as pending on the dashboard', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $workspace = createWorkspaceFor($owner);

    $invitation = Invitation::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'invitee@example.com',
        'role' => MembershipRole::Member,
    ]);

    $this->actingAs($invitee)
        ->post(route('invitations.accept', $invitation->token));

    $this->actingAs($invitee)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspaces/Index')
            ->has('pendingInvitations', 0)
            ->has('workspaces', 1)
        );
});

test('invitation preview page shows whether the invite is actionable', function () {
    $owner = User::factory()->create();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
    $workspace = createWorkspaceFor($owner);

    $invitation = Invitation::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'invitee@example.com',
        'role' => MembershipRole::Member,
    ]);

    $this->actingAs($invitee)
        ->get(route('invitations.show', $invitation->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('invitations/Accept')
            ->where('invitation.is_pending', true)
            ->where('invitation.email_matches', true)
        );

    $this->actingAs($wrongUser)
        ->get(route('invitations.show', $invitation->token))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('invitation.is_pending', true)
            ->where('invitation.email_matches', false)
        );
});

test('owners and admins can cancel pending invitations', function (MembershipRole $role) {
    $owner = User::factory()->create();
    $actor = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    if ($role === MembershipRole::Owner) {
        $actor = $owner;
    } else {
        addWorkspaceMember($workspace, $actor, $role);
    }

    $invitation = Invitation::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'cancel-me@example.com',
    ]);

    $this->actingAs($actor)
        ->delete(route('invitations.destroy', $invitation))
        ->assertRedirect(route('workspaces.show', $workspace));

    $this->assertDatabaseMissing('invitations', ['id' => $invitation->id]);
})->with([
    'owner' => MembershipRole::Owner,
    'admin' => MembershipRole::Admin,
]);

test('members cannot cancel invitations', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $workspace = createWorkspaceFor($owner);
    addWorkspaceMember($workspace, $member, MembershipRole::Member);

    $invitation = Invitation::factory()->pending()->create([
        'workspace_id' => $workspace->id,
        'invited_by' => $owner->id,
        'email' => 'cancel-me@example.com',
    ]);

    $this->actingAs($member)
        ->delete(route('invitations.destroy', $invitation))
        ->assertForbidden();

    $this->assertDatabaseHas('invitations', ['id' => $invitation->id]);
});

test('invitation emails are normalized to lowercase', function () {
    $owner = User::factory()->create();
    $workspace = createWorkspaceFor($owner);

    $this->actingAs($owner)
        ->post(route('workspaces.invitations.store', $workspace), [
            'email' => '  MixedCase@Example.COM ',
            'role' => MembershipRole::Member->value,
        ])
        ->assertRedirect(route('workspaces.show', $workspace));

    $this->assertDatabaseHas('invitations', [
        'workspace_id' => $workspace->id,
        'email' => 'mixedcase@example.com',
    ]);
});
