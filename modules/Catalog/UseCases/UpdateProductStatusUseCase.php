<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Exceptions\ProductNotFoundException;

final readonly class UpdateProductStatusUseCase
{
    public function __construct(private ProductRepositoryInterface $products) {}

    public function execute(int $id, int $status): ProductOutput
    {
        $product = $this->products->find($id);

        if ($product === null) {
            throw new ProductNotFoundException;
        }

        return ProductOutput::fromModel($this->products->updateStatus($product, ProductStatusEnum::from($status)));
    }
}
