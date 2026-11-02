<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateProductRequest extends FormRequest
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
        return $this->productRules(true);
    }

    /** @return array<int, \Closure(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $quantities = collect($this->input('variants', []))->sum(fn (array $variant): int => (int) $variant['stock_quantity']);

            if ($quantities !== (int) $this->input('stock_quantity')) {
                $validator->errors()->add('variants', 'The sum of variant quantities must equal the total stock quantity.');
            }

            if ($this->filled('primary_image_id') && $this->filled('primary_image_index')) {
                $validator->errors()->add('primary_image_id', 'Choose either an existing image or a newly uploaded image as primary.');
            }

            $primaryImageIndex = $this->input('primary_image_index');
            $images = $this->file('images', []);

            if ($primaryImageIndex !== null && (! is_array($images) || $primaryImageIndex >= count($images))) {
                $validator->errors()->add('primary_image_index', 'The selected primary image must be one of the uploaded images.');
            }
        }];
    }

    /** @return array<string, array<int, mixed>> */
    protected function productRules(bool $requireImages): array
    {
        return [
            'status' => ['sometimes', Rule::in(['draft', 'published'])],
            'gender' => ['nullable', Rule::in(['boy', 'girl', 'unisex'])],
            'translations' => ['required', 'array', 'between:1,2'],
            'translations.*.locale_code' => ['required', Rule::in(['fr', 'en']), 'distinct'],
            'translations.0.name' => ['required', 'string', 'max:255'],
            'translations.*.name' => ['nullable', 'string', 'max:255'],
            'translations.*.slug' => ['nullable', 'string', 'max:255'],
            'translations.*.short_description' => ['nullable', 'string', 'max:1000'],
            'translations.*.description' => ['nullable', 'string', 'max:10000'],
            'price_amount' => ['required', 'integer', 'min:1'],
            'compare_at_price_amount' => ['nullable', 'integer', 'gte:price_amount'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'stock_quantity' => ['required', 'integer', 'min:1'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'collection_ids' => ['sometimes', 'array'],
            'collection_ids.*' => ['integer', 'distinct', 'exists:collections,id'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['string', 'max:80', 'distinct'],
            'variants' => ['required', 'array'],
            'variants.*.attributes' => ['required', 'array'],
            'variants.*.attributes.size' => ['required', 'string'],
            'variants.*.attributes.color' => ['nullable', 'string', 'max:80'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'images' => [$requireImages ? 'required' : 'sometimes', 'array', 'max:10'],
            'images.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'primary_image_index' => ['nullable', 'integer', 'min:0'],
            'primary_image_id' => ['nullable', 'integer'],
        ];
    }
}
