<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class UpdatePackInput
{
    /** @param array<int, ProductTranslationInput> $translations @param array<int, int> $categoryIds @param array<int, int> $collectionIds @param array<int, string> $tags @param array<int, \Illuminate\Http\UploadedFile> $images @param array<int, PackComponentInput>|null $components @param array<int, PackVariantInput>|null $variants */
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
        public ?array $components,
        public ?array $variants,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        $create = CreatePackInput::from($data);

        return new self(
            status: $create->status,
            gender: $create->gender,
            translations: $create->translations,
            priceAmount: $create->priceAmount,
            lowStockThreshold: $create->lowStockThreshold,
            categoryIds: $create->categoryIds,
            collectionIds: $create->collectionIds,
            tags: $create->tags,
            images: $create->images,
            components: array_key_exists('components', $data) ? $create->components : null,
            variants: array_key_exists('variants', $data) ? $create->variants : null,
        );
    }
}
