<?php

namespace Modules\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Orders\Enums\OrderItemTypeEnum;

final class CreateCheckoutRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'idempotency_key' => $this->header('Idempotency-Key'),
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
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:50'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'idempotency_key' => ['required', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:25'],
            'items.*.catalog_item_type' => ['sometimes', Rule::enum(OrderItemTypeEnum::class)],
            'items.*.variant_id' => ['nullable', 'integer', 'distinct', 'required_if:items.*.catalog_item_type,product', 'exists:product_variants,id'],
            'items.*.pack_variant_id' => ['nullable', 'integer', 'distinct', 'required_if:items.*.catalog_item_type,pack', 'exists:pack_variants,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }
}
