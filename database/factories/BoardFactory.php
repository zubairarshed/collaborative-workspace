<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Board>
 */
class BoardFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'workspace_id' => Workspace::factory(),
            'created_by' => User::factory(),
            'name' => ucfirst($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->optional()->sentence(),
            'is_archived' => false,
            'position' => 0,
            'version' => 1,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => ['is_archived' => true]);
    }

    public function forWorkspace(Workspace $workspace, ?User $creator = null): static
    {
        return $this->state(fn (array $attributes) => [
            'workspace_id' => $workspace->id,
            'created_by' => $creator?->id ?? $workspace->owner_id,
        ]);
    }

    public function withDefaultColumns(): static
    {
        return $this->afterCreating(function (Board $board): void {
            foreach (Board::DEFAULT_COLUMNS as $index => $column) {
                BoardColumn::factory()->for($board)->create([
                    'name' => $column['name'],
                    'key' => $column['key'],
                    'position' => $index,
                ]);
            }
        });
    }
}
