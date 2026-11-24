<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Enums\ProductGenderEnum;
use Modules\Catalog\Enums\ProductStatusEnum;

class CreatePackRequest extends FormRequest
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
            'status' => ['sometimes', Rule::enum(ProductStatusEnum::class)],
            'gender' => ['nullable', Rule::enum(ProductGenderEnum::class)],
            'translations' => ['required', 'array', 'size:1'],
            'translations.0.locale_code' => ['required', Rule::in(['fr'])],
            'translations.0.name' => ['required', 'string', 'max:255'],
            'translations.0.slug' => ['nullable', 'string', 'max:255'],
            'translations.0.short_description' => ['nullable', 'string', 'max:1000'],
            'translations.0.description' => ['nullable', 'string', 'max:10000'],
            'price_amount' => ['required', 'integer', 'min:1'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'category_ids' => ['sometimes', 'array'], 'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'collection_ids' => ['sometimes', 'array'], 'collection_ids.*' => ['integer', 'distinct', 'exists:collections,id'],
            'tags' => ['sometimes', 'array', 'max:20'], 'tags.*' => ['string', 'max:80', 'distinct'],
            'images' => ['required', 'array', 'max:10'], 'images.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'components' => ['required', 'array', 'min:2', 'max:20'],
            'components.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'components.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'variants' => ['sometimes', 'array', 'max:50'],
            'variants.*.attributes' => ['sometimes', 'array'],
            'variants.*.attributes.*' => ['string', 'max:100'],
            'variants.*.is_default' => ['sometimes', 'boolean'],
            'variants.*.is_active' => ['sometimes', 'boolean'],
            'variants.*.component_mappings' => ['required', 'array', 'min:2', 'max:20'],
            'variants.*.component_mappings.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'variants.*.component_mappings.*.product_variant_id' => ['required', 'integer', 'distinct', 'exists:product_variants,id'],
        ];
    }
}
