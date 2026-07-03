<?php

namespace App\Actions\Tasks;

use App\Enums\TaskPriority;
use App\Models\Task;

class UpdateTask
{
    /**
     * Update a task's editable attributes.
     *
     * Column and position changes are handled by MoveTask.
     *
     * @param  array{
     *     title?: string,
     *     description?: string|null,
     *     priority?: TaskPriority|string,
     *     due_at?: Carbon|string|null,
     *     assignee_id?: int|null
     * }  $data
     */
    public function handle(Task $task, array $data): Task
    {
        $priority = null;
        if (array_key_exists('priority', $data)) {
            $priority = $data['priority'];
            if (is_string($priority)) {
                $priority = TaskPriority::from($priority);
            }
        }

        $task->fill(array_filter(
            [
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'priority' => $priority,
                'due_at' => array_key_exists('due_at', $data) ? $data['due_at'] : null,
                'assignee_id' => array_key_exists('assignee_id', $data) ? $data['assignee_id'] : null,
            ],
            fn ($value, $key) => array_key_exists($key, $data),
            ARRAY_FILTER_USE_BOTH,
        ));

        $task->save();

        return $task;
    }
}
