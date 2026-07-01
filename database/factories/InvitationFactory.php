<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invitation>
 */
class InvitationFactory extends Factory
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
            'invited_by' => User::factory(),
            'email' => fake()->unique()->safeEmail(),
            'role' => fake()->randomElement([
                MembershipRole::Admin,
                MembershipRole::Member,
                MembershipRole::Viewer,
            ]),
            'token' => Str::random(40),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'rejected_at' => null,
        ];
    }

    /**
     * A still-actionable invitation (default state, expires in the future).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
            'rejected_at' => null,
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => now()->subDays(2),
            'rejected_at' => null,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => null,
            'rejected_at' => now()->subDays(2),
        ]);
    }

    /**
     * Past its expiry but never accepted or rejected.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(3),
            'accepted_at' => null,
            'rejected_at' => null,
        ]);
    }
}
