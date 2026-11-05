<?php

namespace Modules\Catalog\DTOs\Inputs;

final readonly class ListShopProductsInput
{
    public function __construct(public ?int $collectionId, public bool $discounted) {}

    /** @param array{collection_id?: int, discounted?: bool} $data */
    public static function from(array $data): self
    {
        return new self(isset($data['collection_id']) ? (int) $data['collection_id'] : null, (bool) ($data['discounted'] ?? false));
    }
}
