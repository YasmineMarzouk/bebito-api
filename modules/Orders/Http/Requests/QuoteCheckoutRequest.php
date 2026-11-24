<?php

namespace Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Orders\Enums\OrderItemTypeEnum;

final class QuoteCheckoutRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'items' => array_map(
                fn (array $item): array => ['catalog_item_type' => 'product', ...$item],
                $this->input('items', []),
            ),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_type' => ['sometimes', Rule::enum(OrderItemTypeEnum::class)],
            'items.*.variant_id' => ['nullable', 'integer', 'distinct', 'required_if:items.*.catalog_item_type,product', 'exists:product_variants,id'],
            'items.*.pack_variant_id' => ['nullable', 'integer', 'distinct', 'required_if:items.*.catalog_item_type,pack', 'exists:pack_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
