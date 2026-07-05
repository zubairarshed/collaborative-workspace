<?php

namespace App\Actions\Tasks;

use App\Events\Tasks\TaskMoved;
use App\Models\BoardColumn;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MoveTask
{
    /**
     * Offset applied while shuffling so the unique (board_column_id, position)
     * constraint is never violated mid-update.
     */
    private const TEMP_OFFSET = 100000;

    /**
     * Move a task to another column and/or position within the same board.
     */
    public function handle(Task $task, User $actor, BoardColumn $targetColumn, ?int $position = null): Task
    {
        if ($targetColumn->board_id !== $task->board_id) {
            throw ValidationException::withMessages([
                'board_column_id' => 'The target column must belong to the same board.',
            ]);
        }

        $sourceColumn = $task->column;

        $moved = DB::transaction(function () use ($task, $targetColumn, $position): Task {
            $sourceColumn = $task->column;
            $movingWithinSameColumn = $sourceColumn->is($targetColumn);

            $orderedIds = $targetColumn->tasks()
                ->when($movingWithinSameColumn, fn ($query) => $query->whereKeyNot($task->id))
                ->orderBy('position')
                ->pluck('id')
                ->all();

            $position ??= count($orderedIds);
            $position = max(0, min($position, count($orderedIds)));

            array_splice($orderedIds, $position, 0, [$task->id]);

            if (! $movingWithinSameColumn) {
                $sourceColumn->tasks()
                    ->whereKeyNot($task->id)
                    ->orderBy('position')
                    ->get()
                    ->each(function (Task $remaining, int $index): void {
                        $remaining->update(['position' => $index]);
                    });
            }

            $tasks = Task::query()->whereIn('id', $orderedIds)->get()->keyBy('id');

            foreach ($orderedIds as $id) {
                $tasks[$id]->update(['position' => $tasks[$id]->position + self::TEMP_OFFSET]);
            }

            foreach ($orderedIds as $index => $id) {
                $tasks[$id]->update([
                    'board_column_id' => $targetColumn->id,
                    'position' => $index,
                ]);
            }

            return $task->fresh();
        });

        if (! $sourceColumn->is($targetColumn)) {
            event(new TaskMoved($moved, $actor, $sourceColumn, $targetColumn));
        }

        return $moved;
    }
}
