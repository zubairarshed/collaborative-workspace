<?php

namespace App\Actions\Boards;

use App\Models\Board;

class DeleteBoard
{
    /**
     * Soft-delete a board.
     */
    public function handle(Board $board): void
    {
        $board->delete();
    }
}
