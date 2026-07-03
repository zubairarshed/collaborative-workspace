<?php

namespace App\Http\Controllers;

use App\Actions\Workspaces\CreateWorkspace;
use App\Actions\Workspaces\DeleteWorkspace;
use App\Actions\Workspaces\UpdateWorkspace;
use App\Http\Requests\StoreWorkspaceRequest;
use App\Http\Requests\UpdateWorkspaceRequest;
use App\Models\Board;
use App\Models\Invitation;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    /**
     * List the workspaces the current user belongs to.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Workspace::class);

        $user = $request->user();

        $workspaces = $user->workspaces()
            ->withCount('members')
            ->orderBy('name')
            ->get()
            ->map(fn (Workspace $workspace) => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'description' => $workspace->description,
                'role' => $workspace->pivot->role,
                'members_count' => $workspace->members_count,
                'created_at' => $workspace->created_at,
            ]);

        $pendingInvitations = Invitation::query()
            ->with('workspace')
            ->pending()
            ->where('email', Str::lower($user->email))
            ->latest()
            ->get()
            ->map(fn (Invitation $invitation) => [
                'id' => $invitation->id,
                'token' => $invitation->token,
                'role' => $invitation->role,
                'expires_at' => $invitation->expires_at,
                'workspace' => [
                    'id' => $invitation->workspace->id,
                    'name' => $invitation->workspace->name,
                    'description' => $invitation->workspace->description,
                ],
            ]);

        return Inertia::render('workspaces/Index', [
            'workspaces' => $workspaces,
            'pendingInvitations' => $pendingInvitations,
        ]);
    }

    /**
     * Create a new workspace owned by the current user.
     */
    public function store(StoreWorkspaceRequest $request, CreateWorkspace $action): RedirectResponse
    {
        Gate::authorize('create', Workspace::class);

        $workspace = $action->handle($request->user(), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace created.')]);

        return to_route('workspaces.show', $workspace);
    }

    /**
     * Show a single workspace with its members and pending invitations.
     */
    public function show(Request $request, Workspace $workspace): Response
    {
        Gate::authorize('view', $workspace);

        $user = $request->user();
        $role = $user->roleIn($workspace);

        $members = $workspace->memberships()
            ->with('user')
            ->get()
            ->map(fn ($membership) => [
                'id' => $membership->id,
                'role' => $membership->role,
                'joined_at' => $membership->joined_at,
                'is_owner' => $membership->user_id === $workspace->owner_id,
                'is_self' => $membership->user_id === $user->id,
                'user' => [
                    'id' => $membership->user->id,
                    'name' => $membership->user->name,
                    'email' => $membership->user->email,
                ],
            ]);

        $invitations = $workspace->invitations()
            ->pending()
            ->latest()
            ->get()
            ->map(fn ($invitation) => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'expires_at' => $invitation->expires_at,
                'created_at' => $invitation->created_at,
            ]);

        $boards = $workspace->boards()
            ->orderBy('position')
            ->get()
            ->map(fn (Board $board) => [
                'id' => $board->id,
                'name' => $board->name,
                'slug' => $board->slug,
                'description' => $board->description,
                'is_archived' => $board->is_archived,
                'position' => $board->position,
            ]);

        return Inertia::render('workspaces/Show', [
            'workspace' => [
                'id' => $workspace->id,
                'name' => $workspace->name,
                'slug' => $workspace->slug,
                'description' => $workspace->description,
                'owner_id' => $workspace->owner_id,
            ],
            'role' => $role,
            'members' => $members,
            'invitations' => $invitations,
            'boards' => $boards,
            'can' => [
                'update' => $user->can('update', $workspace),
                'delete' => $user->can('delete', $workspace),
                'manageMembers' => $user->can('create', [Invitation::class, $workspace]),
                'createBoard' => $user->can('create', [Board::class, $workspace]),
            ],
        ]);
    }

    /**
     * Update workspace settings.
     */
    public function update(UpdateWorkspaceRequest $request, Workspace $workspace, UpdateWorkspace $action): RedirectResponse
    {
        Gate::authorize('update', $workspace);

        $action->handle($workspace, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace updated.')]);

        return to_route('workspaces.show', $workspace);
    }

    /**
     * Delete a workspace.
     */
    public function destroy(Workspace $workspace, DeleteWorkspace $action): RedirectResponse
    {
        Gate::authorize('delete', $workspace);

        $action->handle($workspace);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Workspace deleted.')]);

        return to_route('dashboard');
    }
}
