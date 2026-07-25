<?php

namespace App\Actions\Boards;

use App\Events\Boards\ColumnUpdated;
use App\Models\BoardColumn;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateColumn
{
    /**
     * Update a column's editable attributes.
     *
     * Position changes are handled separately by ReorderColumns.
     *
     * @param  array{name?: string, wip_limit?: int|null}  $data
     */
    public function handle(BoardColumn $column, User $actor, array $data, int $expectedVersion): BoardColumn
    {
        /** @var list<string> $changedFields */
        $changedFields = [];

        $column = DB::transaction(function () use ($column, $data, $expectedVersion, &$changedFields): BoardColumn {
            // OCC (ADR-004): re-read the row under a pessimistic lock so the
            // version compare stays atomic with the write.
            $column = BoardColumn::query()->lockForUpdate()->findOrFail($column->id);
            $column->assertVersion($expectedVersion);

            $column->fill(array_filter(
                [
                    'name' => $data['name'] ?? null,
                    'wip_limit' => $data['wip_limit'] ?? null,
                ],
                fn ($value, $key) => array_key_exists($key, $data),
                ARRAY_FILTER_USE_BOTH,
            ));

            $changedFields = array_keys($column->getDirty());

            if ($changedFields !== []) {
                $column->bumpVersion();
            }

            $column->save();

            return $column;
        });

        if ($changedFields !== []) {
            event(new ColumnUpdated($column, $actor, $changedFields));
        }

        return $column;
    }
}
