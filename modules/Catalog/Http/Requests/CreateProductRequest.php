<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Catalog\Enums\ProductGenderEnum;
use Modules\Catalog\Enums\ProductStatusEnum;

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

            if ($this->has('stock_quantity') && $quantities !== (int) $this->input('stock_quantity')) {
                $validator->errors()->add('variants', 'The sum of variant quantities must equal the total stock quantity.');
            }

        }];
    }

    /** @return array<string, array<int, mixed>> */
    protected function productRules(bool $requireImages): array
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
            'compare_at_price_amount' => ['nullable', 'integer', 'gte:price_amount'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:99.99'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'category_ids' => ['sometimes', 'array'],
            'category_ids.*' => ['integer', 'distinct', 'exists:categories,id'],
            'collection_ids' => ['sometimes', 'array'],
            'collection_ids.*' => ['integer', 'distinct', 'exists:collections,id'],
            'tags' => ['sometimes', 'array', 'max:20'],
            'tags.*' => ['string', 'max:80', 'distinct'],
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer', 'distinct'],
            'variants.*.attributes' => ['required', 'array:size,color'],
            'variants.*.attributes.size' => ['required', 'string', 'max:80'],
            'variants.*.attributes.color' => ['nullable', 'string', 'max:80'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
            'variants.*.low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'images' => [$requireImages ? 'required' : 'sometimes', 'array', 'max:10'],
            'images.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'primary_image_id' => ['nullable', 'integer'],
        ];
    }
}
