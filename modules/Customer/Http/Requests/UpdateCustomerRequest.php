<?php

namespace Modules\Customer\Http\Requests;

final class UpdateCustomerRequest extends CreateCustomerRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['password'] = ['nullable', 'string', 'min:8', 'max:255'];

        return $rules;
    }
}
