<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }

    public function completed(): static
    {
        return $this->state(['status' => 'completed']);
    }
}
