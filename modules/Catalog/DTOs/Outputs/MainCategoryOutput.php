<?php

namespace Modules\Catalog\DTOs\Outputs;

use Modules\Catalog\Models\MainCategory;

final readonly class MainCategoryOutput
{
    public function __construct(
        public int $position,
        public CategoryOutput $category,
    ) {}

    public static function fromModel(MainCategory $mainCategory): self
    {
        return new self(
            position: $mainCategory->position,
            category: CategoryOutput::fromModel($mainCategory->category),
        );
    }
}
