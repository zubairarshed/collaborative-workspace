<?php

namespace App\Events\Boards;

use App\Models\Board;
use App\Models\User;

final class BoardUpdated
{
    /**
     * The board's version after the update (ADR-004); realtime clients
     * reconcile their local copy against it.
     */
    public readonly int $version;

    /**
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public readonly Board $board,
        public readonly User $actor,
        public readonly array $changedFields,
    ) {
        $this->version = $board->currentVersion();
    }
}
