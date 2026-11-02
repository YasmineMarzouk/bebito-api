<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class CreateCollectionInput
{
    /** @param array<int, CatalogTranslationInput> $translations */
    public function __construct(
        public bool $isActive,
        public bool $isFeatured,
        public int $sortOrder,
        public array $translations,
    ) {}

    /** @param array{is_active?: bool, is_featured?: bool, sort_order?: int, translations: array<int, array{locale_code: string, name: string, slug: string, description?: ?string}>} $data */
    public static function from(array $data): self
    {
        return new self(
            isActive: (bool) ($data['is_active'] ?? true),
            isFeatured: (bool) ($data['is_featured'] ?? false),
            sortOrder: (int) ($data['sort_order'] ?? 0),
            translations: array_map(CatalogTranslationInput::from(...), $data['translations']),
        );
    }
}
