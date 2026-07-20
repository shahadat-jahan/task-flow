<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskAttachment>
 */
class TaskAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $filename = fake()->word().'.'.fake()->fileExtension();

        return [
            'task_id' => Task::factory(),
            'uploaded_by' => User::factory(),
            'original_filename' => $filename,
            'stored_path' => 'attachments/'.fake()->uuid().'/'.$filename,
            'mime_type' => fake()->mimeType(),
            'size_bytes' => fake()->numberBetween(1024, 5 * 1024 * 1024),
        ];
    }
}
