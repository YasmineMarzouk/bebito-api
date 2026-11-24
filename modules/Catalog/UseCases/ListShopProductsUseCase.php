<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\ListShopProductsInput;
use Modules\Catalog\DTOs\Outputs\ProductOutput;

final readonly class ListShopProductsUseCase
{
    public function __construct(private ProductRepositoryInterface $products) {}

    /** @return array<int, ProductOutput> */
    public function execute(ListShopProductsInput $input): array
    {
        return $this->products->shop($input->collectionId, $input->discounted)->map(ProductOutput::fromModel(...))->all();
    }
}
