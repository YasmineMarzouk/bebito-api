<?php

namespace Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RegisterCustomerRequest extends FormRequest
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
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:50'],
            'preferred_locale_code' => ['sometimes', Rule::in(['fr', 'en'])],
            'marketing_opt_in' => ['sometimes', 'boolean'],
            'shopping_for' => ['required', Rule::in(['gift', 'my_bebito'])],
            'gender' => ['required_if:shopping_for,my_bebito', 'nullable', Rule::in(['mamito', 'papito'])],
            'children' => ['required_if:shopping_for,my_bebito', 'array', 'min:1'],
            'children.*.name' => ['required_with:children', 'string', 'max:255'],
            'children.*.birth_date' => ['required_with:children', 'date', 'before_or_equal:today'],
            'children.*.gender' => ['required_with:children', Rule::in(['female', 'male'])],
        ];
    }
}
