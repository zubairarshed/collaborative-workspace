<?php

namespace App\Http\Requests;

use App\Models\BoardColumn;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveTaskRequest extends FormRequest
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
            'board_column_id' => [
                'required',
                'integer',
                Rule::exists('board_columns', 'id'),
                function (string $attribute, mixed $value, callable $fail): void {
                    $task = $this->route('task');

                    if (! $task instanceof Task) {
                        return;
                    }

                    $targetColumn = BoardColumn::query()->find($value);

                    if ($targetColumn === null) {
                        return;
                    }

                    if ($targetColumn->board_id !== $task->board_id) {
                        $fail('The target column must belong to the same board.');
                    }
                },
            ],
            'position' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function targetColumn(): BoardColumn
    {
        return BoardColumn::query()->findOrFail($this->integer('board_column_id'));
    }

    public function targetPosition(): ?int
    {
        return $this->has('position') ? $this->integer('position') : null;
    }
}
