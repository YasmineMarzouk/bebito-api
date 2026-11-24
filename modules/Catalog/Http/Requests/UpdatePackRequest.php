<?php

namespace Modules\Catalog\Http\Requests;

final class UpdatePackRequest extends CreatePackRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['images'][0] = 'sometimes';
        $rules['components'][0] = 'sometimes';
        $rules['variants'][0] = 'sometimes';

        return $rules;
    }
}
