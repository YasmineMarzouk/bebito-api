<?php

namespace Modules\Support\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'size:8', 'regex:/^[0-9+\s()\-]+$/'],
            'subject' => ['required', 'string', 'min:2', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }
}
