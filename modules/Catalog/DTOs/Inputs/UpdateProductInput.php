<?php

namespace Modules\Catalog\DTOs\Inputs;

use Illuminate\Http\UploadedFile;

final readonly class UpdateProductInput
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
        public ?int $primaryImageId,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        $create = CreateProductInput::from($data);

        return new self(
            $create->status,
            $create->gender,
            $create->translations,
            $create->priceAmount,
            $create->compareAtPriceAmount,
            $create->discountPercentage,
            $create->stockQuantity,
            $create->lowStockThreshold,
            $create->categoryIds,
            $create->collectionIds,
            $create->tags,
            $create->variants,
            $create->images,
            array_key_exists('primary_image_id', $data) && $data['primary_image_id'] !== null ? (int) $data['primary_image_id'] : null,
        );
    }
}
