<?php

namespace Database\Factories;

use App\Models\Board;
use App\Models\BoardColumn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BoardColumn>
 */
class BoardColumnFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'board_id' => Board::factory(),
            'name' => ucfirst(fake()->unique()->word()),
            'key' => null,
            'position' => 0,
            'wip_limit' => null,
            'version' => 1,
        ];
    }

    public function withWipLimit(int $limit): static
    {
        return $this->state(fn (array $attributes) => ['wip_limit' => $limit]);
    }
}
