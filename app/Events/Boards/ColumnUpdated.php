<?php

namespace App\Events\Boards;

use App\Models\BoardColumn;
use App\Models\User;

final class ColumnUpdated
{
    /**
     * The column's version after the update (ADR-004); realtime clients
     * reconcile their local copy against it.
     */
    public readonly int $version;

    /**
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public readonly BoardColumn $column,
        public readonly User $actor,
        public readonly array $changedFields,
    ) {
        $this->version = $column->currentVersion();
    }
}
