<?php

namespace Database\Factories;

use App\Enums\NotificationType;
use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $task = Task::factory();

        return [
            'user_id' => User::factory(),
            'type' => NotificationType::TaskAssigned,
            'subject_type' => Task::class,
            'subject_id' => $task,
            'data' => ['actor_name' => fake()->name(), 'task_title' => fake()->sentence(3)],
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => ['read_at' => now()]);
    }
}
