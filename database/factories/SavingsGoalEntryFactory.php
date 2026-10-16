<?php

namespace Database\Factories;

use App\Models\SavingsGoal;
use App\Models\SavingsGoalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavingsGoalEntry>
 */
class SavingsGoalEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'savings_goal_id' => SavingsGoal::factory(),
            'type' => fake()->randomElement(['contribution', 'withdrawal']),
            'amount' => fake()->randomFloat(2, 0.01, 1000),
            'entry_date' => fake()->date(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
