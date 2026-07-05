<?php

namespace App\Http\Controllers;

use App\Actions\Memberships\RemoveMember;
use App\Actions\Memberships\UpdateMembershipRole;
use App\Enums\MembershipRole;
use App\Http\Requests\UpdateMembershipRequest;
use App\Models\Membership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MembershipController extends Controller
{
    /**
     * Change a member's role.
     */
    public function update(UpdateMembershipRequest $request, Membership $membership, UpdateMembershipRole $action): RedirectResponse
    {
        Gate::authorize('update', $membership);

        $action->handle(
            $membership,
            $request->user(),
            MembershipRole::from($request->validated('role')),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Member role updated.')]);

        return to_route('workspaces.show', $membership->workspace_id);
    }

    /**
     * Remove a member from the workspace (or leave it).
     */
    public function destroy(Membership $membership, RemoveMember $action): RedirectResponse
    {
        Gate::authorize('delete', $membership);

        $isSelf = $membership->user_id === request()->user()->id;
        $workspaceId = $membership->workspace_id;

        $action->handle($membership, request()->user());

        Inertia::flash('toast', ['type' => 'success', 'message' => $isSelf ? __('You left the workspace.') : __('Member removed.')]);

        return $isSelf
            ? to_route('dashboard')
            : to_route('workspaces.show', $workspaceId);
    }
}
