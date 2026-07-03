<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'queue_enabled' => ['nullable', 'boolean'],
            'default_delay_seconds' => ['nullable', 'integer', 'min:1'],
            'max_retry' => ['nullable', 'integer', 'min:0', 'max:10'],
            'retry_delay_seconds' => ['nullable', 'integer', 'min:1'],
            'load_balancing_mode' => ['nullable', 'in:least_queue,round_robin,fixed,priority'],
            'automatic_failover' => ['nullable', 'boolean'],
            'round_robin_enabled' => ['nullable', 'boolean'],
            'offline_redistribute' => ['nullable', 'boolean'],
            'fixed_sender_id' => ['nullable', 'integer'],
            'status_check_interval_seconds' => ['nullable', 'integer', 'min:30'],
        ];
    }
}
