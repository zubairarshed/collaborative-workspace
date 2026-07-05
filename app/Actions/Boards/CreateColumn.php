<?php

namespace App\Actions\Boards;

use App\Events\Boards\ColumnCreated;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\User;

class CreateColumn
{
    /**
     * Append a new column to the end of a board.
     *
     * @param  array{name: string, wip_limit?: int|null}  $data
     */
    public function handle(Board $board, User $actor, array $data): BoardColumn
    {
        $column = $board->columns()->create([
            'name' => $data['name'],
            'wip_limit' => $data['wip_limit'] ?? null,
            'position' => ($board->columns()->max('position') ?? -1) + 1,
        ]);

        event(new ColumnCreated($column, $actor));

        return $column;
    }
}
