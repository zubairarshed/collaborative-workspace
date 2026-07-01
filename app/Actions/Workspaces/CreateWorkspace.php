<?php

namespace App\Actions\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Str;

class CreateWorkspace
{
    /**
     * Create a workspace owned by the given user.
     *
     * The owner's membership is created automatically by the Workspace
     * model's "created" event.
     *
     * @param  array{name: string, description?: string|null}  $data
     */
    public function handle(User $owner, array $data): Workspace
    {
        return Workspace::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'owner_id' => $owner->id,
        ]);
    }

    /**
     * Generate a slug that is unique across workspaces.
     */
    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $suffix = 2;

        while (Workspace::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
