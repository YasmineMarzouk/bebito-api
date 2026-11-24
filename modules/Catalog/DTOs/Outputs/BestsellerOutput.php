<?php

namespace Modules\Catalog\DTOs\Outputs;

final readonly class BestsellerOutput
{
    public function __construct(
        public int $position,
        public ProductOutput $product,
    ) {}
}
