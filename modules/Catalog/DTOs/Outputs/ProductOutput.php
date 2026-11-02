<?php

namespace Modules\Catalog\DTOs\Outputs;

use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

final readonly class ProductOutput
{
    /** @param array<int, array<string, mixed>> $translations @param array<int, array<string, mixed>> $variants @param array<int, array<string, mixed>> $images @param array<int, array{id: int, name: string}> $categories @param array<int, array{id: int, name: string}> $collections @param array<int, string> $tags */
    public function __construct(
        public int $id,
        public string $status,
        public ?string $gender,
        public ?string $sku,
        public int $priceAmount,
        public ?int $compareAtPriceAmount,
        public ?float $discountPercentage,
        public int $stockQuantity,
        public int $lowStockThreshold,
        public string $inventoryStatus,
        public array $translations,
        public array $variants,
        public array $images,
        public array $categories,
        public array $collections,
        public array $tags,
    ) {}

    public static function fromModel(Product $product): self
    {
        return new self(
            id: $product->id,
            status: $product->status,
            gender: $product->gender,
            sku: $product->sku,
            priceAmount: $product->price_amount,
            compareAtPriceAmount: $product->compare_at_price_amount,
            discountPercentage: $product->discount_percentage,
            stockQuantity: $product->stock_quantity,
            lowStockThreshold: $product->low_stock_threshold,
            inventoryStatus: self::inventoryStatus($product),
            translations: $product->translations->map(fn ($translation): array => [
                'localeCode' => $translation->locale_code,
                'name' => $translation->name,
                'slug' => $translation->slug,
                'shortDescription' => $translation->short_description,
                'description' => $translation->description,
            ])->all(),
            variants: $product->variants->map(self::variantOutput(...))->values()->all(),
            images: $product->assets->map(fn ($asset): array => [
                'id' => $asset->id,
                'url' => rtrim((string) config('app.url'), '/').'/storage/'.ltrim($asset->path, '/'),
                'sortOrder' => $asset->sort_order,
                'isPrimary' => $asset->is_primary,
            ])->all(),
            categories: $product->categories->map(fn ($category): array => [
                'id' => $category->id,
                'name' => $category->translations->firstWhere('locale_code', 'fr')?->name ?? $category->translations->first()?->name ?? '',
            ])->all(),
            collections: $product->collections->map(fn ($collection): array => [
                'id' => $collection->id,
                'name' => $collection->translations->firstWhere('locale_code', 'fr')?->name ?? $collection->translations->first()?->name ?? '',
            ])->all(),
            tags: $product->tags->pluck('name')->values()->all(),
        );
    }

    /** @return array<string, mixed> */
    private static function variantOutput(ProductVariant $variant): array
    {
        $price = $variant->prices->firstWhere('currency_code', 'TND');

        return [
            'id' => $variant->id,
            'sku' => $variant->sku,
            'attributes' => $variant->attributes ?? [],
            'isDefault' => $variant->is_default,
            'priceAmount' => $price?->price_amount,
            'compareAtPriceAmount' => $price?->compare_at_price_amount,
            'stockQuantity' => $variant->inventoryItem?->quantity,
            'lowStockThreshold' => $variant->inventoryItem?->low_stock_threshold,
        ];
    }

    private static function inventoryStatus(Product $product): string
    {
        if ($product->stock_quantity === 0) {
            return 'sold_out';
        }

        if ($product->variants->contains(fn (ProductVariant $variant): bool => ($variant->inventoryItem?->quantity ?? 0) === 0)) {
            return 'partially_available';
        }

        if ($product->stock_quantity < $product->low_stock_threshold) {
            return 'last_chance';
        }

        return 'in_stock';
    }
}
