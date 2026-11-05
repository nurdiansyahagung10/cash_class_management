<?php

namespace Database\Factories;

use App\Models\PendingMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PendingMember>
 */
class PendingMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'student_no' => fake()->unique()->numerify('########'),
            'password' => 'password',
            'created_by' => User::factory()->staff(),
            'expires_at' => now()->addDays(3),
        ];
    }

    /**
     * Indicate that the candidate was not verified in time.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}
