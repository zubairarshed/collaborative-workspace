<?php

namespace App\Actions\Boards;

use App\Models\Board;

class UpdateBoard
{
    /**
     * Update a board's editable attributes.
     *
     * The slug is intentionally left stable so existing links keep working.
     *
     * @param  array{name?: string, description?: string|null}  $data
     */
    public function handle(Board $board, array $data): Board
    {
        $board->fill(array_filter(
            [
                'name' => $data['name'] ?? null,
                'description' => $data['description'] ?? null,
            ],
            fn ($value, $key) => array_key_exists($key, $data),
            ARRAY_FILTER_USE_BOTH,
        ));

        $board->save();

        return $board;
    }
}
