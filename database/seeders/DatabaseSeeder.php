<?php

namespace Database\Seeders;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\TaskComment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => '12345678',
            'email_verified_at' => now(),
        ]);

        $users = User::factory(5)->create(['password' => '12345678']);

        $projectNames = ['Product', 'Growth', 'Infrastructure', 'Design'];
        $projectColors = ['#7C3AED', '#2563EB', '#059669', '#DB2777'];

        $projects = collect($projectNames)->map(function (string $name, int $index) use ($users, $projectColors) {
            return Project::factory()->create([
                'name' => $name,
                'color' => $projectColors[$index],
                'owner_id' => $users->random()->id,
            ]);
        });

        $tagDefinitions = [
            ['name' => 'Design', 'color' => '#DB2777'],
            ['name' => 'API', 'color' => '#3B82F6'],
            ['name' => 'Bug', 'color' => '#EF4444'],
            ['name' => 'UX', 'color' => '#8B5CF6'],
            ['name' => 'Backend', 'color' => '#059669'],
            ['name' => 'Frontend', 'color' => '#06B6D4'],
            ['name' => 'Urgent', 'color' => '#F97316'],
            ['name' => 'Documentation', 'color' => '#6B7280'],
            ['name' => 'Performance', 'color' => '#EAB308'],
            ['name' => 'Security', 'color' => '#DC2626'],
        ];

        collect($tagDefinitions)->each(
            fn (array $tag) => Tag::factory()->create($tag)
        );

        $statuses = TaskStatus::cases();
        $priorities = TaskPriority::cases();

        $tasks = Task::factory(25)
            ->sequence(fn () => [
                'status' => fake()->randomElement($statuses),
                'priority' => fake()->randomElement($priorities),
                'assignee_id' => fake()->optional(0.85)->passthrough($users->random()->id),
                'created_by' => $users->random()->id,
                'project_id' => fake()->optional(0.9)->passthrough($projects->random()->id),
            ])
            ->create();

        $tasks->each(function (Task $task) use ($users): void {
            $commentCount = fake()->numberBetween(0, 4);
            for ($i = 0; $i < $commentCount; $i++) {
                TaskComment::factory()->create([
                    'task_id' => $task->id,
                    'user_id' => $users->random()->id,
                ]);
            }

            $attachmentCount = fake()->numberBetween(0, 2);
            for ($i = 0; $i < $attachmentCount; $i++) {
                TaskAttachment::factory()->create([
                    'task_id' => $task->id,
                    'uploaded_by' => $users->random()->id,
                ]);
            }
        });
    }
}
