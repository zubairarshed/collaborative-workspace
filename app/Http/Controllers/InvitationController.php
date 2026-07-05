<?php

namespace App\Http\Controllers;

use App\Actions\Invitations\AcceptInvitation;
use App\Actions\Invitations\CancelInvitation;
use App\Actions\Invitations\InviteMember;
use App\Enums\MembershipRole;
use App\Http\Requests\StoreInvitationRequest;
use App\Models\Invitation;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    /**
     * Invite a new member to the workspace.
     */
    public function store(StoreInvitationRequest $request, Workspace $workspace, InviteMember $action): RedirectResponse
    {
        Gate::authorize('create', [Invitation::class, $workspace]);

        $action->handle(
            $workspace,
            $request->user(),
            $request->validated('email'),
            MembershipRole::from($request->validated('role')),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation sent.')]);

        return to_route('workspaces.show', $workspace);
    }

    /**
     * Show the invitation acceptance page.
     */
    public function show(string $token, Request $request): Response
    {
        $invitation = Invitation::with('workspace')->where('token', $token)->firstOrFail();

        $isPending = $invitation->accepted_at === null
            && $invitation->rejected_at === null
            && $invitation->expires_at?->isFuture();

        return Inertia::render('invitations/Accept', [
            'invitation' => [
                'token' => $invitation->token,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'workspace_name' => $invitation->workspace->name,
                'is_pending' => (bool) $isPending,
                'email_matches' => Str::lower($request->user()->email) === Str::lower($invitation->email),
            ],
        ]);
    }

    /**
     * Accept an invitation by its token.
     *
     * Authorization is enforced by the token plus an email match inside the
     * action, since the accepting user is not yet a workspace member.
     */
    public function accept(string $token, Request $request, AcceptInvitation $action): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        $membership = $action->handle($invitation, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation accepted.')]);

        return to_route('workspaces.show', $membership->workspace_id);
    }

    /**
     * Cancel (delete) a pending invitation.
     */
    public function destroy(Request $request, Invitation $invitation, CancelInvitation $action): RedirectResponse
    {
        Gate::authorize('delete', $invitation);

        $workspaceId = $invitation->workspace_id;

        $action->handle($invitation, $request->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Invitation cancelled.')]);

        return to_route('workspaces.show', $workspaceId);
    }
}
