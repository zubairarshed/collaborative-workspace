<?php

namespace App\Actions\Boards;

use App\Events\Boards\BoardDeleted;
use App\Models\Board;
use App\Models\User;

class DeleteBoard
{
    /**
     * Soft-delete a board.
     */
    public function handle(Board $board, User $actor): void
    {
        $board->delete();

        event(new BoardDeleted($board, $actor));
    }
}
