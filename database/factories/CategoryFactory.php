<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $index = 0;
        $index++;

        return [
            'user_id' => User::factory(),
            'name' => fake()->word() . ' ' . $index,
            'type' => fake()->randomElement(['income', 'expense']),
            'color' => fake()->hexColor(),
            'icon' => fake()->word(),
        ];
    }

    /**
     * Configure the model factory to assign the category to a specific user.
     */
    public function forUser(User $user): static
    {
        static $index = 0;
        $index++;

        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'name' => fake()->word() . ' ' . $index,
        ]);
    }
}
