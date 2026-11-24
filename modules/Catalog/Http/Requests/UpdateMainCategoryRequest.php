<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateMainCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'position' => ['required', 'integer', 'between:1,6'],
        ];
    }
}
