<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateCollectionRequest extends FormRequest
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
            'is_featured' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:65535'],
            'translations' => ['required', 'array', 'between:1,2'],
            'translations.*.locale_code' => ['required', 'string', Rule::in(['fr', 'en']), 'distinct'],
            'translations.0.name' => ['required', 'string', 'max:255'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'translations.*.description' => ['nullable', 'string', 'max:10000'],
            'banner' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
