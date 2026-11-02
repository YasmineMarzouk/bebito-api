<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class CreateCategoryInput
{
    /** @param array<int, CatalogTranslationInput> $translations */
    public function __construct(public bool $isActive, public array $translations) {}

    /** @param array{is_active?: bool, translations: array<int, array{locale_code: string, name: string, slug: string, description?: ?string}>} $data */
    public static function from(array $data): self
    {
        return new self(
            isActive: (bool) ($data['is_active'] ?? true),
            translations: array_map(CatalogTranslationInput::from(...), $data['translations']),
        );
    }
}
