<?php

namespace Modules\Catalog\DTOs\Inputs;

use Illuminate\Http\UploadedFile;
use Modules\Catalog\Enums\ProductStatusEnum;

final readonly class CreatePackInput
{
    /** @param array<int, ProductTranslationInput> $translations @param array<int, int> $categoryIds @param array<int, int> $collectionIds @param array<int, string> $tags @param array<int, UploadedFile> $images @param array<int, PackComponentInput> $components @param array<int, PackVariantInput> $variants */
    public function __construct(
        public int $status,
        public ?int $gender,
        public array $translations,
        public int $priceAmount,
        public int $lowStockThreshold,
        public array $categoryIds,
        public array $collectionIds,
        public array $tags,
        public array $images,
        public array $components,
        public array $variants,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            status: (int) ($data['status'] ?? ProductStatusEnum::Draft->value),
            gender: array_key_exists('gender', $data) && $data['gender'] !== null ? (int) $data['gender'] : null,
            translations: array_map(ProductTranslationInput::from(...), $data['translations']),
            priceAmount: (int) $data['price_amount'],
            lowStockThreshold: (int) ($data['low_stock_threshold'] ?? 10),
            categoryIds: array_map('intval', $data['category_ids'] ?? []),
            collectionIds: array_map('intval', $data['collection_ids'] ?? []),
            tags: array_values($data['tags'] ?? []),
            images: $data['images'] ?? [],
            components: array_map(PackComponentInput::from(...), $data['components'] ?? []),
            variants: array_map(PackVariantInput::from(...), $data['variants'] ?? []),
        );
    }
}
