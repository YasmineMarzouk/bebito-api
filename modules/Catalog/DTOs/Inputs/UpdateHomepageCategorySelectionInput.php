<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class UpdateHomepageCategorySelectionInput
{
    /** @param array<int, int> $categoryIds */
    public function __construct(public array $categoryIds) {}

    /** @param array{category_ids: array<int, int>} $data */
    public static function from(array $data): self
    {
        return new self(array_map('intval', $data['category_ids']));
    }
}
