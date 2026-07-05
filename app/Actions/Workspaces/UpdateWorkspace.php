<?php

namespace App\Actions\Workspaces;

use App\Events\Workspaces\WorkspaceUpdated;
use App\Models\User;
use App\Models\Workspace;

class UpdateWorkspace
{
    /**
     * Update a workspace's editable attributes.
     *
     * The slug is intentionally left stable so existing links keep working.
     *
     * @param  array{name?: string, description?: string|null}  $data
     */
    public function handle(Workspace $workspace, User $actor, array $data): Workspace
    {
        $workspace->fill(array_filter(
            [
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
            ],
            fn ($value, $key) => array_key_exists($key, $data),
            ARRAY_FILTER_USE_BOTH,
        ));

        $changedFields = array_keys($workspace->getDirty());
        $workspace->save();

        if ($changedFields !== []) {
            event(new WorkspaceUpdated($workspace, $actor, $changedFields));
        }

        return $workspace;
    }
}
