<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class ProductTranslationInput
{
    public function __construct(
        public string $localeCode,
        public string $name,
        public ?string $slug,
        public ?string $shortDescription,
        public ?string $description,
    ) {}

    /** @param array{locale_code: string, name?: string, slug?: ?string, short_description?: ?string, description?: ?string} $data */
    public static function from(array $data): self
    {
        return new self(
            localeCode: $data['locale_code'],
            name: $data['name'] ?? '',
            slug: ($data['slug'] ?? null) ?: null,
            shortDescription: ($data['short_description'] ?? null) ?: null,
            description: ($data['description'] ?? null) ?: null,
        );
    }
}
