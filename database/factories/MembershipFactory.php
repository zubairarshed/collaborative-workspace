<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'user_id' => User::factory(),
            'role' => fake()->randomElement([
                MembershipRole::Admin,
                MembershipRole::Member,
                MembershipRole::Viewer,
            ]),
            'joined_at' => fake()->dateTimeBetween('-1 year'),
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => ['role' => MembershipRole::Owner]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => ['role' => MembershipRole::Admin]);
    }

    public function member(): static
    {
        return $this->state(fn (array $attributes) => ['role' => MembershipRole::Member]);
    }

    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => ['role' => MembershipRole::Viewer]);
    }

    public function role(MembershipRole $role): static
    {
        return $this->state(fn (array $attributes) => ['role' => $role]);
    }
}
