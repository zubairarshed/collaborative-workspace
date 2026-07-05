<?php

namespace App\Actions\Boards;

use App\Events\Boards\BoardArchivalToggled;
use App\Models\Board;
use App\Models\User;

class ArchiveBoard
{
    /**
     * Archive or restore a board without deleting its data.
     */
    public function handle(Board $board, User $actor, bool $archived = true): Board
    {
        $board->update(['is_archived' => $archived]);

        event(new BoardArchivalToggled($board, $actor, $archived));

        return $board;
    }
}
