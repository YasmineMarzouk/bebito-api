<?php

namespace Modules\Catalog\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Outputs\BestsellerOutput;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Catalog\Models\Bestseller;
use Modules\Catalog\Models\Product;

final class UpdateBestsellerUseCase
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function execute(int $productId, int $position): BestsellerOutput
    {
        DB::transaction(function () use ($productId, $position): void {
            $existingBestseller = Bestseller::query()
                ->where('position', $position)
                ->lockForUpdate()
                ->first();

            if ($existingBestseller !== null) {
                $existingBestseller->forceDelete();
            }

            Bestseller::query()->create([
                'product_id' => $productId,
                'position' => $position,
            ]);
        }, attempts: 3);

        /** @var Product $product */
        $product = $this->products->find($productId) ?? throw new \LogicException('Validated product was not found.');

        return new BestsellerOutput(position: $position, product: ProductOutput::fromModel($product));
    }
}
