<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    /**
     * Any authenticated user may create tasks.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in($this->enumValues(TaskStatus::class))],
            'priority' => ['required', Rule::in($this->enumValues(TaskPriority::class))],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'assignee_id' => ['nullable', 'exists:users,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:tags,id'],
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
