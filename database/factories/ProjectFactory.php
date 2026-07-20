<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * @var list<string>
     */
    private const array COLORS = [
        '#7C3AED',
        '#2563EB',
        '#059669',
        '#D97706',
        '#DC2626',
        '#DB2777',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'color' => fake()->randomElement(self::COLORS),
            'owner_id' => User::factory(),
        ];
    }
}
