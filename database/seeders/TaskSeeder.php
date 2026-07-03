<?php

namespace Database\Seeders;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Seed realistic tasks across active boards.
     */
    public function run(): void
    {
        $acme = Workspace::where('slug', 'acme-marketing')->first();
        $globex = Workspace::where('slug', 'globex-engineering')->first();

        if (! $acme || ! $globex) {
            return;
        }

        $contentCalendar = Board::where('workspace_id', $acme->id)
            ->where('slug', 'content-calendar')
            ->first();
        $sprintBoard = Board::where('workspace_id', $globex->id)
            ->where('slug', 'sprint-board')
            ->first();
        $bugTracker = Board::where('workspace_id', $globex->id)
            ->where('slug', 'bug-tracker')
            ->first();

        if ($contentCalendar) {
            $this->seedBoardTasks($contentCalendar, $acme, [
                'todo' => [
                    ['title' => 'Draft Q3 newsletter outline', 'priority' => 'medium'],
                    ['title' => 'Plan social posts for product launch', 'priority' => 'high'],
                ],
                'doing' => [
                    ['title' => 'Write blog post: customer success story', 'priority' => 'high', 'due_days' => 3],
                ],
                'review' => [
                    ['title' => 'Review landing page copy', 'priority' => 'medium'],
                ],
                'done' => [
                    ['title' => 'Publish May newsletter', 'priority' => 'low'],
                ],
            ]);
        }

        if ($sprintBoard) {
            $this->seedBoardTasks($sprintBoard, $globex, [
                'todo' => [
                    ['title' => 'Add board column reorder API tests', 'priority' => 'medium'],
                    ['title' => 'Design task move transaction flow', 'priority' => 'high'],
                ],
                'doing' => [
                    ['title' => 'Implement task model and migration', 'priority' => 'high', 'due_days' => 2],
                    ['title' => 'Wire board show payload with tasks', 'priority' => 'medium', 'due_days' => 5],
                ],
                'review' => [
                    ['title' => 'Code review: board policies', 'priority' => 'medium'],
                ],
                'done' => [
                    ['title' => 'Ship Sprint 2 boards and columns', 'priority' => 'high'],
                ],
            ]);
        }

        if ($bugTracker) {
            $this->seedBoardTasks($bugTracker, $globex, [
                'todo' => [
                    ['title' => 'Invitation email not sent on staging', 'priority' => 'urgent'],
                ],
                'doing' => [
                    ['title' => 'Column delete does not compact positions in edge case', 'priority' => 'high'],
                ],
                'review' => [
                    ['title' => 'Archived board still appears in workspace list', 'priority' => 'low'],
                ],
                'done' => [
                    ['title' => 'Dashboard pending invite count mismatch', 'priority' => 'medium'],
                ],
            ]);
        }
    }

    /**
     * @param  array<string, list<array{title: string, priority: string, due_days?: int}>>  $tasksByColumnKey
     */
    private function seedBoardTasks(Board $board, Workspace $workspace, array $tasksByColumnKey): void
    {
        $columns = $board->columns()->get()->keyBy('key');
        $members = $workspace->members()->get();
        $creator = User::find($workspace->owner_id);

        foreach ($tasksByColumnKey as $columnKey => $tasks) {
            $column = $columns->get($columnKey);

            if (! $column instanceof BoardColumn) {
                continue;
            }

            foreach ($tasks as $position => $task) {
                Task::updateOrCreate(
                    [
                        'board_id' => $board->id,
                        'board_column_id' => $column->id,
                        'title' => $task['title'],
                    ],
                    [
                        'created_by' => $creator?->id,
                        'assignee_id' => $members->random()->id,
                        'description' => null,
                        'priority' => $task['priority'],
                        'due_at' => isset($task['due_days'])
                            ? now()->addDays($task['due_days'])
                            : null,
                        'is_archived' => false,
                        'position' => $position,
                    ],
                );
            }
        }
    }
}
