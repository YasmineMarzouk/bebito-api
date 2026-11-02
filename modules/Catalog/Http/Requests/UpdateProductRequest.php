<?php

namespace Modules\Catalog\Http\Requests;

final class UpdateProductRequest extends CreateProductRequest
{
    public function rules(): array
    {
        return $this->productRules(false);
    }
}
