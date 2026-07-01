<?php

namespace Database\Seeders;

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class MembershipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Each workspace owner gets an Owner membership, then the remaining users
     * are spread across the two workspaces with a mix of roles.
     */
    public function run(): void
    {
        $users = User::orderBy('id')->get();
        $workspaces = Workspace::orderBy('id')->get();

        foreach ($workspaces as $workspace) {
            $this->upsertMembership($workspace, $workspace->owner_id, MembershipRole::Owner);
        }

        if ($workspaces->count() < 2 || $users->count() < 5) {
            return;
        }

        [$acme, $globex] = [$workspaces[0], $workspaces[1]];

        $assignments = [
            [$acme, $users[1], MembershipRole::Admin],
            [$acme, $users[2], MembershipRole::Member],
            [$acme, $users[3], MembershipRole::Viewer],
            [$globex, $users[0], MembershipRole::Admin],
            [$globex, $users[2], MembershipRole::Member],
            [$globex, $users[4], MembershipRole::Viewer],
        ];

        foreach ($assignments as [$workspace, $user, $role]) {
            $this->upsertMembership($workspace, $user->id, $role);
        }
    }

    private function upsertMembership(Workspace $workspace, int $userId, MembershipRole $role): void
    {
        Membership::updateOrCreate(
            ['workspace_id' => $workspace->id, 'user_id' => $userId],
            ['role' => $role, 'joined_at' => now()->subDays(rand(1, 60))],
        );
    }
}
