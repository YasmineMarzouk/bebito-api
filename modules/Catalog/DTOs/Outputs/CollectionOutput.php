<?php

namespace Modules\Catalog\DTOs\Outputs;

use Modules\Catalog\Models\Collection;

final readonly class CollectionOutput
{
    /** @param array<int, CatalogTranslationOutput> $translations */
    public function __construct(
        public int $id,
        public bool $isActive,
        public bool $isFeatured,
        public int $sortOrder,
        public int $productCount,
        public ?string $bannerUrl,
        public array $translations,
    ) {}

    public static function fromModel(Collection $collection): self
    {
        return new self(
            id: $collection->id,
            isActive: $collection->is_active,
            isFeatured: $collection->is_featured,
            sortOrder: $collection->sort_order,
            productCount: (int) ($collection->products_count ?? 0),
            bannerUrl: $collection->primaryAsset === null ? null : rtrim((string) config('app.url'), '/').'/storage/'.ltrim($collection->primaryAsset->path, '/'),
            translations: $collection->translations->map(CatalogTranslationOutput::fromModel(...))->all(),
        );
    }
}
