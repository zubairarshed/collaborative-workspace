<?php

namespace App\Events\Workspaces;

use App\Models\User;
use App\Models\Workspace;

final class WorkspaceDeleted
{
    public function __construct(
        public readonly Workspace $workspace,
        public readonly User $actor,
    ) {}
}
