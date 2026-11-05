<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\UpdateHomepageProductSelectionInput;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Catalog\Exceptions\HomepageProductsMustBePublishedException;

final class UpdateHomepageProductSelectionUseCase
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    /** @return array<int, ProductOutput> */
    public function execute(UpdateHomepageProductSelectionInput $input): array
    {
        $products = $this->products->findByIds($input->productIds);

        if ($products->count() !== count($input->productIds) || $products->contains(fn ($product): bool => $product->status !== 'published')) {
            throw new HomepageProductsMustBePublishedException;
        }

        return $this->products->replaceHomepageSelection($input->productIds)->map(ProductOutput::fromModel(...))->all();
    }
}
