<?php

namespace App\Events\Boards;

use App\Models\BoardColumn;
use App\Models\User;

final class ColumnUpdated
{
    /**
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public readonly BoardColumn $column,
        public readonly User $actor,
        public readonly array $changedFields,
    ) {}
}
