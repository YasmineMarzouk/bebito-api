<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['contact_email' => ['required', 'email'], 'phone' => ['nullable', 'string', 'max:50'], 'address' => ['nullable', 'string', 'max:1000'], 'instagram' => ['nullable', 'url', 'max:255'], 'facebook' => ['nullable', 'url', 'max:255'], 'tiktok' => ['nullable', 'url', 'max:255'], 'pinterest' => ['nullable', 'url', 'max:255'], 'standard_shipping_amount' => ['required', 'integer', 'min:0'], 'free_shipping_threshold' => ['required', 'integer', 'min:0'], 'express_shipping_amount' => ['required', 'integer', 'min:0'], 'delivery_time_days' => ['required', 'string', 'max:50'], 'express_delivery_time_days' => ['required', 'string', 'max:50'], 'default_currency' => ['required', 'string', 'size:3']];
    }
}
