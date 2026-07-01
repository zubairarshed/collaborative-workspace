<?php

namespace App\Actions\Workspaces;

use App\Models\Workspace;

class DeleteWorkspace
{
    /**
     * Soft-delete a workspace.
     */
    public function handle(Workspace $workspace): void
    {
        $workspace->delete();
    }
}
