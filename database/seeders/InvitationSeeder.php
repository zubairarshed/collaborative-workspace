<?php

namespace Database\Seeders;

use App\Enums\MembershipRole;
use App\Models\Invitation;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class InvitationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Adds a few pending invitations per workspace, plus one accepted and one
     * expired invitation so the pending() scope can be exercised.
     */
    public function run(): void
    {
        $workspaces = Workspace::orderBy('id')->get();

        if ($workspaces->isEmpty()) {
            return;
        }

        foreach ($workspaces as $workspace) {
            Invitation::factory()
                ->count(2)
                ->pending()
                ->create([
                    'workspace_id' => $workspace->id,
                    'invited_by' => $workspace->owner_id,
                ]);
        }

        $first = $workspaces->first();

        Invitation::factory()->accepted()->create([
            'workspace_id' => $first->id,
            'invited_by' => $first->owner_id,
            'role' => MembershipRole::Member,
        ]);

        Invitation::factory()->expired()->create([
            'workspace_id' => $first->id,
            'invited_by' => $first->owner_id,
            'role' => MembershipRole::Viewer,
        ]);
    }
}
