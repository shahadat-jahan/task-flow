<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * @var list<string>
     */
    private const array COLORS = [
        '#EF4444',
        '#F97316',
        '#EAB308',
        '#22C55E',
        '#06B6D4',
        '#3B82F6',
        '#8B5CF6',
        '#EC4899',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'color' => fake()->randomElement(self::COLORS),
        ];
    }
}
