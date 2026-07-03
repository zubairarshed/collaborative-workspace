<?php

namespace App\Actions\Boards;

use App\Models\Board;

class ArchiveBoard
{
    /**
     * Archive or restore a board without deleting its data.
     */
    public function handle(Board $board, bool $archived = true): Board
    {
        $board->update(['is_archived' => $archived]);

        return $board;
    }
}
