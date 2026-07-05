<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $workspace = Workspace::factory();

        return [
            'workspace_id' => $workspace,
            'user_id' => User::factory(),
            'type' => ActivityType::WorkspaceUpdated,
            'subject_type' => Workspace::class,
            'subject_id' => $workspace,
            'data' => ['subject_label' => fake()->words(3, true), 'fields' => ['name']],
        ];
    }
}
