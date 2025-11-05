<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    protected static ?string $locale = 'en_US';
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(4, true),
            'description' => fake()->paragraph(),
            'status' => fake()->randomElement(['A', 'C', 'H', 'X'])
        ];
    }
}
