<?php

namespace Modules\Promotions\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Promotions\Enums\CouponTypeEnum;

class CreateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return $this->couponRules();
    }

    /** @return array<string, array<int, mixed>> */
    protected function couponRules(): array
    {
        return [
            'code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'type' => ['required', Rule::in(CouponTypeEnum::values())],
            'percentage' => ['nullable', 'integer', 'between:1,100', 'required_if:type,'.CouponTypeEnum::Percentage->value, 'prohibited_if:type,'.CouponTypeEnum::Fixed->value],
            'fixed_amount' => ['nullable', 'integer', 'min:1', 'required_if:type,'.CouponTypeEnum::Fixed->value, 'prohibited_if:type,'.CouponTypeEnum::Percentage->value],
            'min_order_amount' => ['sometimes', 'integer', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
