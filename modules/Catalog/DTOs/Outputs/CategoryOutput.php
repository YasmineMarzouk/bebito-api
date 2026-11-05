<?php

namespace Modules\Catalog\DTOs\Outputs;

use Modules\Catalog\Models\Category;

final readonly class CategoryOutput
{
    /** @param array<int, CatalogTranslationOutput> $translations */
    public function __construct(
        public int $id,
        public bool $isActive,
        public bool $isHomepageFeatured,
        public ?int $homepagePosition,
        public int $productCount,
        public ?string $bannerUrl,
        public array $translations,
    ) {}

    public static function fromModel(Category $category): self
    {
        return new self(
            id: $category->id,
            isActive: $category->is_active,
            isHomepageFeatured: $category->is_homepage_featured,
            homepagePosition: $category->homepage_position,
            productCount: (int) ($category->products_count ?? 0),
            bannerUrl: $category->primaryAsset === null ? null : rtrim((string) config('app.url'), '/').'/storage/'.ltrim($category->primaryAsset->path, '/'),
            translations: $category->translations->map(CatalogTranslationOutput::fromModel(...))->all(),
        );
    }
}
