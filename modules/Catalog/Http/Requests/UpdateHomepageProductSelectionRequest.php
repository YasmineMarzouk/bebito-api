<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateHomepageProductSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'product_ids' => ['present', 'array', 'max:4'],
            'product_ids.*' => ['integer', 'distinct', 'exists:products,id'],
        ];
    }
}
