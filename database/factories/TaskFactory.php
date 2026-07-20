<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
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
            'title' => fake()->sentence(4),
            'description' => fake()->optional(0.7)->paragraph(),
            'status' => fake()->randomElement(TaskStatus::cases()),
            'priority' => fake()->randomElement(TaskPriority::cases()),
            'due_date' => fake()->optional(0.6)->dateTimeBetween('now', '+3 months'),
            'assignee_id' => User::factory(),
            'created_by' => User::factory(),
            'project_id' => Project::factory(),
        ];
    }

    /**
     * Attach 1–3 random tags after the task is created.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Task $task): void {
            if (Tag::query()->doesntExist()) {
                return;
            }

            $tagIds = Tag::query()
                ->inRandomOrder()
                ->limit(fake()->numberBetween(1, 3))
                ->pluck('id');

            $task->tags()->attach($tagIds);
        });
    }
}
