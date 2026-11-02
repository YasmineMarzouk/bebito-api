<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Catalog\Exceptions\ProductNotFoundException;

final readonly class GetProductUseCase
{
    public function __construct(private ProductRepositoryInterface $products) {}

    public function execute(int $id): ProductOutput
    {
        $product = $this->products->find($id);

        if ($product === null) {
            throw new ProductNotFoundException;
        }

        return ProductOutput::fromModel($product);
    }
}
