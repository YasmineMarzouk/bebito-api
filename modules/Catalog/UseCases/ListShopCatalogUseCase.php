<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\ShopCatalogRepositoryInterface;
use Modules\Catalog\Contracts\Services\PackAvailabilityServiceInterface;
use Modules\Catalog\DTOs\Inputs\ListShopCatalogInput;
use Modules\Catalog\DTOs\Outputs\CategoryOutput;
use Modules\Catalog\DTOs\Outputs\PackOutput;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Catalog\DTOs\Outputs\ShopCatalogOutput;

final readonly class ListShopCatalogUseCase
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
        private ProductRepositoryInterface $products,
        private PackRepositoryInterface $packs,
        private ShopCatalogRepositoryInterface $catalog,
        private PackAvailabilityServiceInterface $availability,
    ) {}

    public function execute(ListShopCatalogInput $input): ShopCatalogOutput
    {
        $catalogPage = $this->catalog->paginate($input->categoryId, $input->discounted, $input->perPage, $input->page);
        $identifiers = collect($catalogPage->items());
        $products = $this->products->findShopByIds($identifiers->where('catalog_item_type', 'product')->pluck('id')->map(fn (int|string $id): int => (int) $id)->all())->keyBy('id');
        $packs = $this->packs->findShopByIds($identifiers->where('catalog_item_type', 'pack')->pluck('id')->map(fn (int|string $id): int => (int) $id)->all())->keyBy('id');
        $items = $identifiers->map(function (object $identifier) use ($products, $packs): array {
            if ($identifier->catalog_item_type === 'product') {
                $product = $products->get($identifier->id);

                return ['catalogItemType' => 'product', ...get_object_vars(ProductOutput::fromModel($product))];
            }

            $pack = $packs->get($identifier->id);

            return ['catalogItemType' => 'pack', ...get_object_vars(PackOutput::fromModel($pack, $this->availability->for($pack)))];
        })->all();

        return new ShopCatalogOutput(
            categories: $this->categories->shop()->map(CategoryOutput::fromModel(...))->all(),
            items: $items,
            currentPage: $catalogPage->currentPage(),
            lastPage: $catalogPage->lastPage(),
            perPage: $catalogPage->perPage(),
            total: $catalogPage->total(),
        );
    }
}
