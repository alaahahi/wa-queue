<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\MessageSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnqueueMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:20'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:4096'],
            'source' => ['required', 'string', Rule::in(MessageSource::values())],
            'event' => ['nullable', 'string', 'max:100'],
            'priority' => ['nullable', 'integer', 'min:1', 'max:10'],
            'unique_key' => ['nullable', 'string', 'max:255'],
            'scheduled_at' => ['nullable', 'date'],
            'max_retry' => ['nullable', 'integer', 'min:0', 'max:10'],
            'created_by' => ['nullable', 'string', 'max:255'],
        ];
    }
}
