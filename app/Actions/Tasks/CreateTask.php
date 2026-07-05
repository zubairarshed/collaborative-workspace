<?php

namespace App\Actions\Tasks;

use App\Enums\TaskPriority;
use App\Events\Tasks\TaskCreated;
use App\Models\BoardColumn;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;

class CreateTask
{
    /**
     * Create a task at the end of a column.
     *
     * @param  array{
     *     title: string,
     *     description?: string|null,
     *     priority?: TaskPriority|string,
     *     due_at?: Carbon|string|null,
     *     assignee_id?: int|null
     * }  $data
     */
    public function handle(BoardColumn $column, User $creator, array $data): Task
    {
        $priority = $data['priority'] ?? TaskPriority::Medium;
        if (is_string($priority)) {
            $priority = TaskPriority::from($priority);
        }

        $task = $column->tasks()->create([
            'board_id' => $column->board_id,
            'created_by' => $creator->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'priority' => $priority,
            'due_at' => $data['due_at'] ?? null,
            'assignee_id' => $data['assignee_id'] ?? null,
            'position' => ($column->tasks()->max('position') ?? -1) + 1,
        ]);

        event(new TaskCreated($task, $creator));

        return $task;
    }
}
