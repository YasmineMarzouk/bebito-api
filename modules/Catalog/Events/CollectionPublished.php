<?php

namespace Modules\Catalog\Events;

final readonly class CollectionPublished
{
    public function __construct(public int $collectionId) {}
}
