<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\CreateProductInput;
use Modules\Catalog\DTOs\Inputs\ProductTranslationInput;
use Modules\Catalog\DTOs\Inputs\UpdateProductInput;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Models\Tag;
use Modules\Core\Models\Asset;

final class ProductRepository implements ProductRepositoryInterface
{
    public function all(): Collection
    {
        return Product::query()->with($this->relations())->latest('id')->get();
    }

    public function find(int $id): ?Product
    {
        return Product::query()->with($this->relations())->find($id);
    }

    public function findAsset(int $productId, int $assetId): ?Asset
    {
        return Asset::query()
            ->where('resource_type', 'product')
            ->where('resource_id', $productId)
            ->find($assetId);
    }

    public function setPrimaryAsset(int $productId, int $assetId): bool
    {
        return DB::transaction(function () use ($productId, $assetId): bool {
            $asset = $this->findAsset($productId, $assetId);

            if ($asset === null) {
                return false;
            }

            Asset::query()
                ->where('resource_type', 'product')
                ->where('resource_id', $productId)
                ->update(['is_primary' => false]);
            $asset->update(['is_primary' => true]);

            return true;
        });
    }

    public function setFirstAssetAsPrimary(int $productId): void
    {
        $asset = Asset::query()
            ->where('resource_type', 'product')
            ->where('resource_id', $productId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        if ($asset !== null) {
            $this->setPrimaryAsset($productId, $asset->id);
        }
    }

    /** @param array<int, ProductTranslationInput> $translations */
    public function create(CreateProductInput $input, array $translations): Product
    {
        return DB::transaction(function () use ($input, $translations): Product {
            $product = Product::query()->create($this->productAttributes($input));
            $product->update(['sku' => sprintf('BEB-%06d', $product->id)]);
            $this->storeTranslations($product, $translations);
            $this->syncOrganization($product, $input->categoryIds, $input->collectionIds, $input->tags);

            foreach ($input->variants as $variantInput) {
                $variant = $this->createVariant($product, $variantInput->attributes, false, $variantInput->stockQuantity, $variantInput->lowStockThreshold);
                $variant->prices()->create([
                    'currency_code' => 'TND',
                    'price_amount' => $input->priceAmount,
                    'compare_at_price_amount' => $this->compareAtPriceAmount($input),
                ]);
            }

            return $this->find($product->id) ?? $product;
        });
    }

    /** @param array<int, ProductTranslationInput> $translations */
    public function update(Product $product, UpdateProductInput $input, array $translations): Product
    {
        return DB::transaction(function () use ($product, $input, $translations): Product {
            $product->update($this->productAttributes($input));
            $this->storeTranslations($product, $translations);
            $this->syncOrganization($product, $input->categoryIds, $input->collectionIds, $input->tags);

            $product->variants()->delete();
            foreach ($input->variants as $variantInput) {
                $variant = $this->createVariant($product, $variantInput->attributes, false, $variantInput->stockQuantity, $variantInput->lowStockThreshold);
                $variant->prices()->create([
                    'currency_code' => 'TND',
                    'price_amount' => $input->priceAmount,
                    'compare_at_price_amount' => $this->compareAtPriceAmount($input),
                ]);
            }

            return $this->find($product->id) ?? $product;
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    /** @return array<int, string> */
    private function relations(): array
    {
        return ['translations', 'variants.prices', 'variants.inventoryItem', 'assets', 'categories.translations', 'collections.translations', 'tags'];
    }

    /** @param array<int, ProductTranslationInput> $translations */
    private function storeTranslations(Product $product, array $translations): void
    {
        foreach ($translations as $translation) {
            $product->translations()->updateOrCreate(
                ['locale_code' => $translation->localeCode],
                [
                    'name' => $translation->name,
                    'slug' => $translation->slug,
                    'short_description' => $translation->shortDescription,
                    'description' => $translation->description,
                ],
            );
        }
    }

    /** @param array<int, int> $categoryIds @param array<int, int> $collectionIds @param array<int, string> $tagNames */
    private function syncOrganization(Product $product, array $categoryIds, array $collectionIds, array $tagNames): void
    {
        $product->categories()->sync($categoryIds);
        $product->collections()->sync($collectionIds);
        $tagIds = collect($tagNames)
            ->map(fn (string $name): int => Tag::query()->firstOrCreate(['name' => trim($name)])->id)
            ->all();
        $product->tags()->sync($tagIds);
    }

    /** @param array<string, string> $attributes */
    private function createVariant(Product $product, array $attributes, bool $isDefault, int $quantity, int $threshold): ProductVariant
    {
        $number = $product->variants()->count() + 1;
        $variant = $product->variants()->create([
            'sku' => sprintf('BEB-%06d-%02d', $product->id, $number),
            'attributes' => $attributes,
            'is_default' => $isDefault,
        ]);
        $variant->inventoryItem()->create(['quantity' => $quantity, 'low_stock_threshold' => $threshold]);

        return $variant;
    }

    /** @return array{status: string, gender: ?string, price_amount: int, compare_at_price_amount: ?int, discount_percentage: ?float, stock_quantity: int, low_stock_threshold: int} */
    private function productAttributes(CreateProductInput|UpdateProductInput $input): array
    {
        return [
            'status' => $input->status,
            'gender' => $input->gender,
            'price_amount' => $input->priceAmount,
            'compare_at_price_amount' => $this->compareAtPriceAmount($input),
            'discount_percentage' => $this->discountPercentage($input),
            'stock_quantity' => $input->stockQuantity,
            'low_stock_threshold' => $input->lowStockThreshold,
        ];
    }

    private function compareAtPriceAmount(CreateProductInput|UpdateProductInput $input): ?int
    {
        if ($input->compareAtPriceAmount !== null) {
            return $input->compareAtPriceAmount;
        }

        if ($input->discountPercentage === null || $input->discountPercentage <= 0) {
            return null;
        }

        return (int) round($input->priceAmount / (1 - ($input->discountPercentage / 100)));
    }

    private function discountPercentage(CreateProductInput|UpdateProductInput $input): ?float
    {
        $compareAtPriceAmount = $this->compareAtPriceAmount($input);

        if ($compareAtPriceAmount === null || $compareAtPriceAmount <= $input->priceAmount) {
            return null;
        }

        return round((($compareAtPriceAmount - $input->priceAmount) / $compareAtPriceAmount) * 100, 2);
    }
}
