<?php

namespace Modules\Core\DTOs\Inputs;

final readonly class StoreAssetInput
{
    public function __construct(
        public string $resourceType,
        public int $resourceId,
        public string $directory,
        public string $disk = 'public',
        public int $sortOrder = 0,
        public bool $isPrimary = false,
    ) {}
}
