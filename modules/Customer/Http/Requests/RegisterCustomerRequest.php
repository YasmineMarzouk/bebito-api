<?php

namespace Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'phone' => ['required', 'string', 'max:50'],
            'preferred_locale_code' => ['sometimes', Rule::in(['fr', 'en'])],
            'marketing_opt_in' => ['sometimes', 'boolean'],
            'shopping_for' => ['required', Rule::in(['gift', 'my_bebito'])],
            'gender' => ['required_if:shopping_for,my_bebito', 'nullable', Rule::in(['mamito', 'papito'])],
            'children' => ['required_if:shopping_for,my_bebito', 'array', 'min:1'],
            'children.*.name' => ['required_with:children', 'string', 'max:255'],
            'children.*.birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'children.*.newborn' => ['sometimes', 'boolean'],
            'children.*.gender' => ['required_with:children', Rule::in(['female', 'male'])],
        ];
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            foreach ($this->input('children', []) as $index => $child) {
                $isNewborn = filter_var($child['newborn'] ?? false, FILTER_VALIDATE_BOOLEAN);

                if (! $isNewborn && blank($child['birth_date'] ?? null)) {
                    $validator->errors()->add("children.{$index}.birth_date", 'The birth date field is required unless the child is unborn.');
                }
            }
        }];
    }
}
