<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class UpdateCollectionInput
{
    /** @param array<int, CatalogTranslationInput> $translations */
    public function __construct(
        public bool $isActive,
        public bool $isFeatured,
        public int $sortOrder,
        public array $translations,
    ) {}

    /** @param array{is_active: bool, is_featured: bool, sort_order: int, translations: array<int, array{locale_code: string, name: string, slug: string, description?: ?string}>} $data */
    public static function from(array $data): self
    {
        return new self(
            isActive: $data['is_active'],
            isFeatured: $data['is_featured'],
            sortOrder: $data['sort_order'],
            translations: array_map(CatalogTranslationInput::from(...), $data['translations']),
        );
    }
}
