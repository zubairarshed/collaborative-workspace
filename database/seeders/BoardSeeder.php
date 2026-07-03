<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class BoardSeeder extends Seeder
{
    /**
     * Seed boards with default workflow columns for each workspace.
     */
    public function run(): void
    {
        $acme = Workspace::where('slug', 'acme-marketing')->first();
        $globex = Workspace::where('slug', 'globex-engineering')->first();

        if (! $acme || ! $globex) {
            return;
        }

        $boards = [
            [
                'workspace' => $acme,
                'name' => 'Content Calendar',
                'slug' => 'content-calendar',
                'description' => 'Editorial planning, drafts, and publishing schedule.',
                'position' => 0,
                'is_archived' => false,
            ],
            [
                'workspace' => $acme,
                'name' => 'Brand Campaigns',
                'slug' => 'brand-campaigns',
                'description' => 'Campaign briefs, creative assets, and launch checklists.',
                'position' => 1,
                'is_archived' => false,
            ],
            [
                'workspace' => $globex,
                'name' => 'Sprint Board',
                'slug' => 'sprint-board',
                'description' => 'Current sprint work items and delivery tracking.',
                'position' => 0,
                'is_archived' => false,
            ],
            [
                'workspace' => $globex,
                'name' => 'Bug Tracker',
                'slug' => 'bug-tracker',
                'description' => 'Reported defects, triage, and fix verification.',
                'position' => 1,
                'is_archived' => false,
            ],
            [
                'workspace' => $globex,
                'name' => 'Legacy Releases',
                'slug' => 'legacy-releases',
                'description' => 'Archived release coordination from prior quarters.',
                'position' => 2,
                'is_archived' => true,
            ],
        ];

        foreach ($boards as $attributes) {
            $workspace = $attributes['workspace'];
            unset($attributes['workspace']);

            $board = Board::updateOrCreate(
                ['workspace_id' => $workspace->id, 'slug' => $attributes['slug']],
                [
                    ...$attributes,
                    'created_by' => $workspace->owner_id,
                ],
            );

            $this->seedDefaultColumns($board);
        }
    }

    private function seedDefaultColumns(Board $board): void
    {
        foreach (Board::DEFAULT_COLUMNS as $index => $column) {
            BoardColumn::updateOrCreate(
                ['board_id' => $board->id, 'position' => $index],
                [
                    'name' => $column['name'],
                    'key' => $column['key'],
                    'wip_limit' => $column['key'] === 'doing' ? 3 : null,
                ],
            );
        }
    }
}
