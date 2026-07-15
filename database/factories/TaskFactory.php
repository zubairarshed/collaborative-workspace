<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @var list<TaskPriority>
     */
    public const PRIORITIES = [
        TaskPriority::Low,
        TaskPriority::Medium,
        TaskPriority::High,
        TaskPriority::Urgent,
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_id' => Board::factory()->withDefaultColumns(),
            'board_column_id' => function (array $attributes): int {
                return Board::query()
                    ->findOrFail($attributes['board_id'])
                    ->columns()
                    ->inRandomOrder()
                    ->value('id');
            },
            'created_by' => User::factory(),
            'assignee_id' => null,
            'title' => ucfirst(fake()->unique()->sentence(4)),
            'description' => fake()->optional()->paragraph(),
            'priority' => fake()->randomElement(self::PRIORITIES),
            'due_at' => fake()->optional(0.6)->dateTimeBetween('now', '+30 days'),
            'is_archived' => false,
            'position' => function (array $attributes): int {
                return (Task::query()
                    ->where('board_column_id', $attributes['board_column_id'])
                    ->max('position') ?? -1) + 1;
            },
            'version' => 1,
        ];
    }

    public function forColumn(BoardColumn $column, ?User $creator = null, ?User $assignee = null): static
    {
        return $this->state(fn (array $attributes) => [
            'board_id' => $column->board_id,
            'board_column_id' => $column->id,
            'created_by' => $creator?->id ?? $attributes['created_by'] ?? null,
            'assignee_id' => $assignee?->id,
            'position' => (Task::query()
                ->where('board_column_id', $column->id)
                ->max('position') ?? -1) + 1,
        ]);
    }

    public function withAssignee(User $user): static
    {
        return $this->state(fn (array $attributes) => ['assignee_id' => $user->id]);
    }

    public function priority(TaskPriority $priority): static
    {
        return $this->state(fn (array $attributes) => ['priority' => $priority]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['is_archived' => true]);
    }
}
