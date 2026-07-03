<?php

namespace App\Actions\Boards;

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateBoard
{
    /**
     * Create a board within a workspace, seeded with the default columns.
     *
     * @param  array{name: string, description?: string|null}  $data
     */
    public function handle(Workspace $workspace, User $creator, array $data): Board
    {
        return DB::transaction(function () use ($workspace, $creator, $data): Board {
            $board = $workspace->boards()->create([
                'created_by' => $creator->id,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($workspace, $data['name']),
                'description' => $data['description'] ?? null,
                'position' => ($workspace->boards()->max('position') ?? -1) + 1,
            ]);

            $this->createDefaultColumns($board);

            return $board;
        });
    }

    /**
     * Create the default workflow columns for a new board.
     */
    private function createDefaultColumns(Board $board): void
    {
        foreach (Board::DEFAULT_COLUMNS as $index => $column) {
            $board->columns()->create([
                'name' => $column['name'],
                'key' => $column['key'],
                'position' => $index,
            ]);
        }
    }

    /**
     * Generate a slug that is unique within the workspace.
     */
    private function uniqueSlug(Workspace $workspace, string $name): string
    {
        $base = Str::slug($name) ?: 'board';
        $slug = $base;
        $suffix = 2;

        while ($workspace->boards()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
