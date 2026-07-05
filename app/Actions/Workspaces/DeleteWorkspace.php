<?php

namespace App\Actions\Workspaces;

use App\Events\Workspaces\WorkspaceDeleted;
use App\Models\User;
use App\Models\Workspace;

class DeleteWorkspace
{
    /**
     * Soft-delete a workspace.
     */
    public function handle(Workspace $workspace, User $actor): void
    {
        $workspace->delete();

        event(new WorkspaceDeleted($workspace, $actor));
    }
}
