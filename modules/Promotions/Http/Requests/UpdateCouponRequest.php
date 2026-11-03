<?php

namespace Modules\Promotions\Http\Requests;

final class UpdateCouponRequest extends CreateCouponRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['expires_at'] = ['nullable', 'date_format:Y-m-d'];

        return $rules;
    }
}
