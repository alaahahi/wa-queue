<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSenderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'api_key' => ['sometimes', 'required', 'string', 'min:4'],
            'delay_seconds' => ['nullable', 'integer', 'min:1', 'max:300'],
            'daily_limit' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10'],
            'enabled' => ['nullable', 'boolean'],
        ];
    }
}
