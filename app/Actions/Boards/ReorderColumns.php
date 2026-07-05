<?php

namespace App\Actions\Boards;

use App\Events\Boards\ColumnsReordered;
use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReorderColumns
{
    /**
     * Offset applied while shuffling so the unique (board_id, position)
     * constraint is never violated mid-update.
     */
    private const TEMP_OFFSET = 100000;

    /**
     * Reassign column positions to match the given ordered list of column IDs.
     *
     * IDs that do not belong to the board are ignored; columns omitted from
     * the list keep their relative order after the provided ones.
     *
     * @param  list<int>  $orderedIds
     */
    public function handle(Board $board, User $actor, array $orderedIds): void
    {
        DB::transaction(function () use ($board, $orderedIds): void {
            $columns = $board->columns()->get()->keyBy('id');

            $ordered = collect($orderedIds)
                ->filter(fn (int $id): bool => $columns->has($id))
                ->values();

            $remaining = $columns->keys()
                ->diff($ordered)
                ->values();

            $finalOrder = $ordered->concat($remaining);

            foreach ($finalOrder as $id) {
                $columns[$id]->update(['position' => $columns[$id]->position + self::TEMP_OFFSET]);
            }

            foreach ($finalOrder as $index => $id) {
                $columns[$id]->update(['position' => $index]);
            }
        });

        event(new ColumnsReordered($board, $actor));
    }
}
