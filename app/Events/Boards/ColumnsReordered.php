<?php

namespace App\Events\Boards;

use App\Models\Board;
use App\Models\User;

final class ColumnsReordered
{
    /**
     * The board's version after the reorder (ADR-004) — column ordering is
     * guarded by the board version; realtime clients reconcile against it.
     */
    public readonly int $version;

    public function __construct(
        public readonly Board $board,
        public readonly User $actor,
    ) {
        $this->version = $board->currentVersion();
    }
}
