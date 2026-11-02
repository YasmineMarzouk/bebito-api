<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\Exceptions\ProductNotFoundException;

final readonly class DeleteProductUseCase
{
    public function __construct(private ProductRepositoryInterface $products) {}

    public function execute(int $id): void
    {
        $product = $this->products->find($id);

        if ($product === null) {
            throw new ProductNotFoundException;
        }

        $this->products->delete($product);
    }
}
