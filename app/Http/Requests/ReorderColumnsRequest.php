<?php

namespace App\Http\Requests;

use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReorderColumnsRequest extends FormRequest
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
            // The board's version — column ordering is board-level state
            // under ADR-004, so a reorder is guarded by the board version.
            'version' => ['required', 'integer', 'min:1'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => [
                'integer',
                function (string $attribute, mixed $value, callable $fail): void {
                    $board = $this->route('board');

                    if (! $board instanceof Board) {
                        return;
                    }

                    if (! $board->columns()->whereKey($value)->exists()) {
                        $fail('One or more columns do not belong to this board.');
                    }
                },
            ],
        ];
    }

    /**
     * Get the validated ordered list of column IDs.
     *
     * @return list<int>
     */
    public function orderedColumnIds(): array
    {
        return array_map('intval', $this->validated('columns'));
    }
}
