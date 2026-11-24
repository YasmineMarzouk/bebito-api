<?php

namespace Modules\Core\DTOs\Outputs;

final readonly class PaginatedOutput
{
    /** @param array<int, mixed> $items */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {}
}
