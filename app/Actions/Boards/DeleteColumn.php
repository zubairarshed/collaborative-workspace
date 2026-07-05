<?php

namespace App\Actions\Boards;

use App\Events\Boards\ColumnDeleted;
use App\Models\BoardColumn;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteColumn
{
    /**
     * Delete a column and compact the remaining columns' positions.
     *
     * A board must always keep at least one column.
     *
     * @throws ValidationException
     */
    public function handle(BoardColumn $column, User $actor): void
    {
        $board = $column->board;

        if ($board->columns()->count() <= 1) {
            throw ValidationException::withMessages([
                'column' => 'A board must have at least one column.',
            ]);
        }

        DB::transaction(function () use ($column, $board): void {
            $column->delete();

            $board->columns()
                ->orderBy('position')
                ->get()
                ->each(function (BoardColumn $remaining, int $index): void {
                    if ($remaining->position !== $index) {
                        $remaining->update(['position' => $index]);
                    }
                });
        });

        event(new ColumnDeleted($column, $board, $actor));
    }
}
