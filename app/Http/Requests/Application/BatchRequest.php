<?php

namespace App\Http\Requests\Application;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BatchRequest extends FormRequest
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
            'batch_index' => [
                'required', 'integer', 'min:0',
            ],

            'rows' => [
                'required', 'array', 'list', 'min:1', 'max:1000',
            ],

            'rows.*' => [
                'required', 'array',
            ],

            'rows.*.external_id' => [
                'required', 'string', 'max:100',
            ],

            'rows.*.created_at' => [
                'nullable', 'date_format:Y-m-d H:i:s',
            ],

            'rows.*.first_name' => [
                'nullable', 'string', 'max:255',
            ],

            'rows.*.last_name' => [
                'nullable', 'string', 'max:255',
            ],

            'rows.*.phone' => [
                'nullable', 'string', 'max:50',
            ],

            'rows.*.email' => [
                'nullable', 'string', 'max:255',
            ],

            'rows.*.city' => [
                'nullable', 'string', 'max:255',
            ],

            'rows.*.source' => [
                'nullable', 'string', 'max:255',
            ],

            'rows.*.utm_campaign' => [
                'nullable', 'string', 'max:255',
            ],

            'rows.*.product' => [
                'nullable', 'string', 'max:255',
            ],

            'rows.*.budget_uah' => [
                'nullable', 'numeric',
            ],

            'rows.*.status' => [
                'nullable', 'string', 'max:100',
            ],

            'rows.*.manager' => [
                'nullable', 'string', 'max:255',
            ],

            'rows.*.comment' => [
                'nullable', 'string',
            ],

            'rows.*.next_contact_at' => [
                'nullable', 'date_format:Y-m-d H:i:s',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'batch_index.required' => 'Індекс пакета є обов’язковим.',
            'batch_index.integer' => 'Індекс пакета повинен бути цілим числом.',
            'batch_index.min' => 'Індекс пакета не може бути меншим за 0.',

            'rows.required' => 'Список рядків є обов’язковим.',
            'rows.array' => 'Рядки повинні бути передані у вигляді масиву.',
            'rows.list' => 'Рядки повинні бути послідовним списком.',
            'rows.min' => 'Пакет повинен містити щонайменше один рядок.',
            'rows.max' => 'Пакет не може містити більше 1000 рядків.',

            'rows.*.required' => 'Кожен рядок є обов’язковим.',
            'rows.*.array' => 'Кожен рядок повинен бути масивом.',

            'rows.*.external_id.required' => 'Зовнішній ID є обов’язковим.',
            'rows.*.external_id.string' => 'Зовнішній ID повинен бути рядком.',
            'rows.*.external_id.max' => 'Зовнішній ID не може перевищувати 100 символів.',

            'rows.*.created_at.date_format' => 'Дата створення повинна мати формат Y-m-d H:i:s.',

            'rows.*.first_name.string' => 'Ім’я повинно бути рядком.',
            'rows.*.first_name.max' => 'Ім’я не може перевищувати 255 символів.',

            'rows.*.last_name.string' => 'Прізвище повинно бути рядком.',
            'rows.*.last_name.max' => 'Прізвище не може перевищувати 255 символів.',

            'rows.*.phone.string' => 'Телефон повинен бути рядком.',
            'rows.*.phone.max' => 'Телефон не може перевищувати 50 символів.',

            'rows.*.email.string' => 'Email повинен бути рядком.',
            'rows.*.email.max' => 'Email не може перевищувати 255 символів.',

            'rows.*.city.string' => 'Місто повинно бути рядком.',
            'rows.*.city.max' => 'Місто не може перевищувати 255 символів.',

            'rows.*.source.string' => 'Джерело повинно бути рядком.',
            'rows.*.source.max' => 'Джерело не може перевищувати 255 символів.',

            'rows.*.utm_campaign.string' => 'UTM-кампанія повинна бути рядком.',
            'rows.*.utm_campaign.max' => 'UTM-кампанія не може перевищувати 255 символів.',

            'rows.*.product.string' => 'Назва продукту повинна бути рядком.',
            'rows.*.product.max' => 'Назва продукту не може перевищувати 255 символів.',

            'rows.*.budget_uah.numeric' => 'Бюджет повинен бути числом.',

            'rows.*.status.string' => 'Статус повинен бути рядком.',
            'rows.*.status.max' => 'Статус не може перевищувати 100 символів.',

            'rows.*.manager.string' => 'Ім’я менеджера повинно бути рядком.',
            'rows.*.manager.max' => 'Ім’я менеджера не може перевищувати 255 символів.',

            'rows.*.comment.string' => 'Коментар повинен бути рядком.',

            'rows.*.next_contact_at.date_format' => 'Дата наступного контакту повинна мати формат Y-m-d H:i:s.',
        ];
    }
}
