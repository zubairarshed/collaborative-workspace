<?php

namespace App\Actions\Boards;

use App\Events\Boards\BoardUpdated;
use App\Models\Board;
use App\Models\User;

class UpdateBoard
{
    /**
     * Update a board's editable attributes.
     *
     * The slug is intentionally left stable so existing links keep working.
     *
     * @param  array{name?: string, description?: string|null}  $data
     */
    public function handle(Board $board, User $actor, array $data): Board
    {
        $board->fill(array_filter(
            [
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
            ],
            fn ($value, $key) => array_key_exists($key, $data),
            ARRAY_FILTER_USE_BOTH,
        ));

        $changedFields = array_keys($board->getDirty());
        $board->save();

        if ($changedFields !== []) {
            event(new BoardUpdated($board, $actor, $changedFields));
        }

        return $board;
    }
}
