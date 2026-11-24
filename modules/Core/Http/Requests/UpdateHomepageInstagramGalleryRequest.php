<?php

namespace Modules\Core\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateHomepageInstagramGalleryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:6'],
            'images.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $images = $this->file('images', []);

            if (! is_array($images)) {
                return;
            }

            foreach (array_keys($images) as $position) {
                if (! is_int($position) || $position < 0 || $position > 5) {
                    $validator->errors()->add('images', 'Images must be assigned to one of the six homepage gallery slots.');

                    return;
                }
            }
        });
    }
}
