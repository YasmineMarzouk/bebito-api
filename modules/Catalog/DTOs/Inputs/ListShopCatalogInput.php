<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class ListShopCatalogInput
{
    public function __construct(
        public ?int $categoryId,
        public bool $discounted,
        public int $page,
        public int $perPage,
    ) {}

    /** @param array{category_id?: int, discounted?: bool, page?: int, per_page?: int} $data */
    public static function from(array $data): self
    {
        return new self(
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            discounted: (bool) ($data['discounted'] ?? false),
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 24),
        );
    }
}
