<?php

namespace Modules\Catalog\DTOs\Inputs;

use Illuminate\Http\UploadedFile;
use Modules\Catalog\Enums\ProductStatusEnum;

final readonly class CreateProductInput
{
    /**
     * @param  array<int, ProductTranslationInput>  $translations
     * @param  array<int, int>  $categoryIds
     * @param  array<int, int>  $collectionIds
     * @param  array<int, string>  $tags
     * @param  array<int, ProductVariantInput>  $variants
     * @param  array<int, UploadedFile>  $images
     */
    public function __construct(
        public int $status,
        public ?int $gender,
        public array $translations,
        public int $priceAmount,
        public ?int $compareAtPriceAmount,
        public ?float $discountPercentage,
        public int $stockQuantity,
        public int $lowStockThreshold,
        public array $categoryIds,
        public array $collectionIds,
        public array $tags,
        public array $variants,
        public array $images,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            status: (int) ($data['status'] ?? ProductStatusEnum::Draft->value),
            gender: array_key_exists('gender', $data) && $data['gender'] !== null ? (int) $data['gender'] : null,
            translations: array_map(ProductTranslationInput::from(...), $data['translations']),
            priceAmount: (int) $data['price_amount'],
            compareAtPriceAmount: array_key_exists('compare_at_price_amount', $data) && $data['compare_at_price_amount'] !== null ? (int) $data['compare_at_price_amount'] : null,
            discountPercentage: array_key_exists('discount_percentage', $data) && $data['discount_percentage'] !== null ? (float) $data['discount_percentage'] : null,
            stockQuantity: (int) $data['stock_quantity'],
            lowStockThreshold: (int) ($data['low_stock_threshold'] ?? 10),
            categoryIds: array_map('intval', $data['category_ids'] ?? []),
            collectionIds: array_map('intval', $data['collection_ids'] ?? []),
            tags: array_values($data['tags'] ?? []),
            variants: array_map(ProductVariantInput::from(...), $data['variants'] ?? []),
            images: $data['images'] ?? [],
        );
    }
}
