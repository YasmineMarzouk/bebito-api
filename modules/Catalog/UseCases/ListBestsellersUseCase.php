<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\BestsellerOutput;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Catalog\Models\Bestseller;

final class ListBestsellersUseCase
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    /** @return array{slots: array<int, BestsellerOutput>, products: array<int, ProductOutput>} */
    public function execute(): array
    {
        $products = $this->products->all()->keyBy('id');

        return [
            'slots' => Bestseller::query()
                ->orderBy('position')
                ->get()
                ->filter(fn (Bestseller $bestseller): bool => $products->has($bestseller->product_id))
                ->map(fn (Bestseller $bestseller): BestsellerOutput => new BestsellerOutput(
                    position: $bestseller->position,
                    product: ProductOutput::fromModel($products->get($bestseller->product_id)),
                ))
                ->values()
                ->all(),
            'products' => $products->map(ProductOutput::fromModel(...))->values()->all(),
        ];
    }
}
