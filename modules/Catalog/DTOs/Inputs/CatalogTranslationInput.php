<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class CatalogTranslationInput
{
    public function __construct(
        public string $localeCode,
        public string $name,
        public ?string $slug,
        public ?string $description,
    ) {}

    /** @param array{locale_code: string, name: string, slug?: ?string, description?: ?string} $data */
    public static function from(array $data): self
    {
        return new self(
            localeCode: $data['locale_code'],
            name: $data['name'] ?? '',
            slug: ($data['slug'] ?? null) ?: null,
            description: $data['description'] ?? null,
        );
    }
}
