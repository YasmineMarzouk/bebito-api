<?php

namespace Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'preferred_locale_code' => ['sometimes', Rule::in(['fr', 'en'])],
            'marketing_opt_in' => ['sometimes', 'boolean'],
            'manual_badge' => ['nullable', Rule::in(['vib'])],
        ];
    }
}
