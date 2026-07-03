<?php

namespace App\Actions\Boards;

use App\Models\Board;
use App\Models\BoardColumn;

class CreateColumn
{
    /**
     * Append a new column to the end of a board.
     *
     * @param  array{name: string, wip_limit?: int|null}  $data
     */
    public function handle(Board $board, array $data): BoardColumn
    {
        return $board->columns()->create([
            'name' => $data['name'],
            'wip_limit' => $data['wip_limit'] ?? null,
            'position' => ($board->columns()->max('position') ?? -1) + 1,
        ]);
    }
}
