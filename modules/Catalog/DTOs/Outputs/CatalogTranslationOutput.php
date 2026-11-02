<?php

namespace Modules\Catalog\DTOs\Outputs;

use Modules\Catalog\Models\CategoryTranslation;
use Modules\Catalog\Models\CollectionTranslation;

final readonly class CatalogTranslationOutput
{
    public function __construct(
        public string $localeCode,
        public string $name,
        public ?string $slug,
        public ?string $description,
    ) {}

    public static function fromModel(CategoryTranslation|CollectionTranslation $translation): self
    {
        return new self(
            localeCode: $translation->locale_code,
            name: $translation->name,
            slug: $translation->slug,
            description: $translation->description,
        );
    }
}
