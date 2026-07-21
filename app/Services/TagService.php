<?php

namespace App\Services;

use App\Models\Tag;

/**
 * Tag persistence, shared by the HTTP controller.
 *
 * The service assumes the caller has already authorized the action and
 * validated the input (including the unique-name rule). It only performs the
 * create; the controller decides how to respond (Inertia redirect vs JSON for
 * the inline tag creator).
 */
class TagService
{
    /**
     * Create a tag.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tag
    {
        return Tag::create($data);
    }
}
