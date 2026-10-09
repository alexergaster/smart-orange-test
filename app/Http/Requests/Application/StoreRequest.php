<?php

namespace App\Http\Requests\Application;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
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
            'total_rows' => [
                'required',
                'integer',
                'min:1',
                'max:1000000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'total_rows.required' => 'Поле total_rows є обов’язковим.',
            'total_rows.integer' => 'Поле total_rows повинно бути цілим числом.',
            'total_rows.min' => 'Мінімальне значення total_rows — 1.',
            'total_rows.max' => 'Максимальне значення total_rows — 1000000.',
        ];
    }
}
