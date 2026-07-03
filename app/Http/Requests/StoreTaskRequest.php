<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Models\Board;
use App\Models\BoardColumn;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'due_at' => ['nullable', 'date'],
            'assignee_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id'),
                function (string $attribute, mixed $value, callable $fail): void {
                    if ($value === null) {
                        return;
                    }

                    $workspace = $this->resolveWorkspace();

                    if ($workspace === null) {
                        return;
                    }

                    if (! $workspace->members()->whereKey($value)->exists()) {
                        $fail('The assignee must be a member of this workspace.');
                    }
                },
            ],
        ];
    }

    /**
     * Ensure the routed column belongs to the routed board.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $board = $this->route('board');
            $column = $this->route('column');

            if (! $board instanceof Board || ! $column instanceof BoardColumn) {
                return;
            }

            if ($column->board_id !== $board->id) {
                $validator->errors()->add('column', 'This column does not belong to the board.');
            }
        });
    }

    private function resolveWorkspace(): ?Workspace
    {
        $workspace = $this->route('workspace');

        return $workspace instanceof Workspace ? $workspace : null;
    }
}
