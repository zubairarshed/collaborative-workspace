<?php

namespace App\Events\Workspaces;

use App\Models\User;
use App\Models\Workspace;

final class WorkspaceUpdated
{
    /**
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public readonly Workspace $workspace,
        public readonly User $actor,
        public readonly array $changedFields,
    ) {}
}
