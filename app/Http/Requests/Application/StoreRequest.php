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
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,xlsx,xls',
                'extensions:csv,xlsx,xls',
                'max:51200',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'Only CSV, XLSX and XLS files are allowed.',
            'file.max' => 'The file may not be larger than 50 MB.',
        ];
    }
}
