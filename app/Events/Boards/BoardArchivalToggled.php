<?php

namespace App\Events\Boards;

use App\Models\Board;
use App\Models\User;

final class BoardArchivalToggled
{
    public function __construct(
        public readonly Board $board,
        public readonly User $actor,
        public readonly bool $archived,
    ) {}
}
