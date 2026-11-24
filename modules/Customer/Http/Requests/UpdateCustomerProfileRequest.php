<?php

namespace Modules\Customer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateCustomerProfileRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user('customer'))],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:50'],
            'children' => ['sometimes', 'array'],
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
