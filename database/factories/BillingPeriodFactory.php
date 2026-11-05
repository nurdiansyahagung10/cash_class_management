<?php

namespace Database\Factories;

use App\Models\BillingPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BillingPeriod>
 */
class BillingPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Kas minggu '.fake()->numberBetween(1, 20),
            'amount' => 10000,
            'due_date' => now()->addDays(7),
            'created_by' => User::factory()->admin(),
        ];
    }
}
