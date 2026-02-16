<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'assigned_to' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'status' => 'todo',
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'due_date' => fake()->optional(0.7)->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(['assigned_to' => $user->id]);
    }

    public function highPriority(): static
    {
        return $this->state(['priority' => 'high']);
    }

    public function done(): static
    {
        return $this->state(['status' => 'done']);
    }

    public function inProgress(): static
    {
        return $this->state(['status' => 'in_progress']);
    }
}
