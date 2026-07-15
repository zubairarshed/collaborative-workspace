<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization is handled by policies at the controller layer.
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
            'version' => ['required', 'integer', 'min:1'],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'priority' => ['sometimes', 'nullable', Rule::enum(TaskPriority::class)],
            'due_at' => ['sometimes', 'nullable', 'date'],
            'assignee_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                function (string $attribute, mixed $value, callable $fail): void {
                    if ($value === null) {
                        return;
                    }

                    $task = $this->route('task');

                    if (! $task instanceof Task) {
                        return;
                    }

                    $workspace = $task->board->workspace;

                    if (! $workspace->members()->whereKey($value)->exists()) {
                        $fail('The assignee must be a member of this workspace.');
                    }
                },
            ],
        ];
    }
}
