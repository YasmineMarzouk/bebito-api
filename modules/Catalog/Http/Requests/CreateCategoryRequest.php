<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $payload = json_decode((string) $this->input('payload', '{}'), true);

        if (is_array($payload)) {
            $this->merge($payload);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['sometimes', 'boolean'],
            'translations' => ['required', 'array', 'size:1'],
            'translations.0.locale_code' => ['required', 'string', Rule::in(['fr'])],
            'translations.0.name' => ['required', 'string', 'max:255'],
            'translations.0.slug' => ['nullable', 'string', 'max:255'],
            'translations.0.description' => ['nullable', 'string', 'max:10000'],
            'banner' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
