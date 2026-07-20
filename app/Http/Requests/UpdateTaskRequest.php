<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Any authenticated user may attempt an update; the policy enforces
     * that only the task's creator may actually perform it.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * All fields are "sometimes" to allow partial updates.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'status' => ['sometimes', 'required', Rule::in($this->enumValues(TaskStatus::class))],
            'priority' => ['sometimes', 'required', Rule::in($this->enumValues(TaskPriority::class))],
            'due_date' => ['sometimes', 'nullable', 'date'],
            'assignee_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'project_id' => ['sometimes', 'nullable', 'exists:projects,id'],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['sometimes', 'exists:tags,id'],
        ];
    }

    /**
     * @param  class-string<\BackedEnum>  $enum
     * @return array<int, string>
     */
    protected function enumValues(string $enum): array
    {
        return array_map(fn ($case) => $case->value, $enum::cases());
    }
}
