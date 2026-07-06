<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'body' => fake()->sentence(),
        ];
    }

    public function forTask(Task $task, ?User $author = null): static
    {
        return $this->state(fn (array $attributes) => [
            'task_id' => $task->id,
            'user_id' => $author?->id ?? $attributes['user_id'] ?? null,
        ]);
    }
}
