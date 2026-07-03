<?php

namespace App\Http\Requests\Central;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'string', 'max:64', 'alpha_dash', Rule::unique('tenants', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'domain' => ['nullable', 'string', 'max:255', Rule::unique('domains', 'domain')],
            'status' => ['nullable', 'in:active,suspended,trial'],
        ];
    }
}
