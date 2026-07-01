<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class WorkspaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates two workspaces owned by the first two users. Owner memberships
     * are handled by the MembershipSeeder.
     */
    public function run(): void
    {
        $owners = User::orderBy('id')->take(2)->get();

        if ($owners->count() < 2) {
            return;
        }

        $workspaces = [
            [
                'name' => 'Acme Marketing',
                'slug' => 'acme-marketing',
                'description' => 'Campaigns, brand assets, and content planning for Acme.',
            ],
            [
                'name' => 'Globex Engineering',
                'slug' => 'globex-engineering',
                'description' => 'Product engineering, specs, and release coordination.',
            ],
        ];

        foreach ($workspaces as $index => $attributes) {
            Workspace::updateOrCreate(
                ['slug' => $attributes['slug']],
                [...$attributes, 'owner_id' => $owners[$index]->id],
            );
        }
    }
}
