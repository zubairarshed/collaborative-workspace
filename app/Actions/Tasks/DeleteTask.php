<?php

namespace App\Actions\Tasks;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

class DeleteTask
{
    /**
     * Offset applied while compacting so the unique (board_column_id, position)
     * constraint is never violated by soft-deleted rows.
     */
    private const TEMP_OFFSET = 100000;

    /**
     * Soft-delete a task and compact the remaining positions in its column.
     */
    public function handle(Task $task): void
    {
        DB::transaction(function () use ($task): void {
            $column = $task->column;

            $task->update(['position' => self::TEMP_OFFSET + $task->id]);
            $task->delete();

            $remaining = $column->tasks()
                ->orderBy('position')
                ->get();

            foreach ($remaining as $remainingTask) {
                $remainingTask->update(['position' => self::TEMP_OFFSET + $remainingTask->id]);
            }

            foreach ($remaining as $index => $remainingTask) {
                $remainingTask->update(['position' => $index]);
            }
        });
    }
}
