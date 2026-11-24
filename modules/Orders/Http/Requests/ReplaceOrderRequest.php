<?php

namespace Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Orders\Enums\OrderItemTypeEnum;

final class ReplaceOrderRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1', 'max:25'],
            'items.*.catalog_item_type' => ['sometimes', Rule::enum(OrderItemTypeEnum::class)],
            'items.*.variant_id' => ['nullable', 'integer', 'distinct', 'required_if:items.*.catalog_item_type,product'],
            'items.*.pack_variant_id' => ['nullable', 'integer', 'distinct', 'required_if:items.*.catalog_item_type,pack'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'coupon_id' => ['present', 'nullable', 'integer'],
            'shipping_address' => ['nullable', 'array'],
            'shipping_address.recipient_name' => ['required_with:shipping_address', 'string', 'max:200'],
            'shipping_address.phone' => ['required_with:shipping_address', 'string', 'max:50'],
            'shipping_address.line_one' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:100'],
            'shipping_address.postal_code' => ['nullable', 'string', 'max:50'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
