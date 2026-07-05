<?php

namespace App\Events\Boards;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\User;

final class ColumnDeleted
{
    public function __construct(
        public readonly BoardColumn $column,
        public readonly Board $board,
        public readonly User $actor,
    ) {}
}
