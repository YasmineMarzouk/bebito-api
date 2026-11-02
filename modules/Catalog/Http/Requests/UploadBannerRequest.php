<?php

namespace Modules\Catalog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadBannerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['banner' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp']];
    }
}
