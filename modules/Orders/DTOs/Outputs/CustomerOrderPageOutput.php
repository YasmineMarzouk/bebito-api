<?php

namespace Modules\Orders\DTOs\Outputs;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Orders\Models\Order;

final readonly class CustomerOrderPageOutput
{
    /** @param array<int, OrderListOutput> $items */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $lastPage,
        public int $perPage,
        public int $total,
    ) {}

    /** @param LengthAwarePaginator<int, Order> $orders */
    public static function fromPaginator(LengthAwarePaginator $orders): self
    {
        return new self(
            $orders->getCollection()->map(OrderListOutput::fromModel(...))->values()->all(),
            $orders->currentPage(),
            $orders->lastPage(),
            $orders->perPage(),
            $orders->total(),
        );
    }
}
