<?php

namespace App\Actions\Boards;

use App\Models\BoardColumn;

class UpdateColumn
{
    /**
     * Update a column's editable attributes.
     *
     * Position changes are handled separately by ReorderColumns.
     *
     * @param  array{name?: string, wip_limit?: int|null}  $data
     */
    public function handle(BoardColumn $column, array $data): BoardColumn
    {
        $column->fill(array_filter(
            [
                'name' => $data['name'] ?? null,
                'wip_limit' => $data['wip_limit'] ?? null,
            ],
            fn ($value, $key) => array_key_exists($key, $data),
            ARRAY_FILTER_USE_BOTH,
        ));

        $column->save();

        return $column;
    }
}
