<?php

namespace App\Http\Requests;

use App\Models\BoardColumn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBoardColumnRequest extends FormRequest
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
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, callable $fail): void {
                    $column = $this->route('column');

                    if (! $column instanceof BoardColumn) {
                        return;
                    }

                    $exists = $column->board->columns()
                        ->where('name', $value)
                        ->whereKeyNot($column->getKey())
                        ->exists();

                    if ($exists) {
                        $fail('A column with this name already exists on the board.');
                    }
                },
            ],
            'wip_limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
