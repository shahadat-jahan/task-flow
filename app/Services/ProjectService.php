<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;

/**
 * Project CRUD operations, shared by the HTTP controller.
 *
 * The service assumes the caller has already authorized the action (projects
 * are globally editable by any authenticated user per the design) and
 * validated the input. It stamps `owner_id` on creation as a creation marker
 * only — not an authorization boundary.
 */
class ProjectService
{
    /**
     * Create a project owned by the given creator.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $creator): Project
    {
        return Project::create([
            ...$data,
            'owner_id' => $creator->id,
        ]);
    }

    /**
     * Update a project.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Project $project, array $data): Project
    {
        $project->update($data);

        return $project;
    }

    /**
     * Delete a project.
     */
    public function delete(Project $project): void
    {
        $project->delete();
    }
}
