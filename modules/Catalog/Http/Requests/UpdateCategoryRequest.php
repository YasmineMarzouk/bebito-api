<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['required', 'boolean'],
            'translations' => ['required', 'array', 'size:1'],
            'translations.0.locale_code' => ['required', 'string', Rule::in(['fr'])],
            'translations.0.name' => ['required', 'string', 'max:255'],
            'translations.0.slug' => ['nullable', 'string', 'max:255'],
            'translations.0.description' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
