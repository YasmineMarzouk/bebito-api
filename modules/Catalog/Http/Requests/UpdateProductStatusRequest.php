<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalog\Enums\ProductStatusEnum;

final class UpdateProductStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ProductStatusEnum::class)],
        ];
    }
}
