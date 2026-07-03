<?php

namespace App\Http\Requests;

use App\Models\Board;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreBoardColumnRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, callable $fail): void {
                    $board = $this->route('board');

                    if (! $board instanceof Board) {
                        return;
                    }

                    if ($board->columns()->where('name', $value)->exists()) {
                        $fail('A column with this name already exists on the board.');
                    }
                },
            ],
            'wip_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
