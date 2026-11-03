<?php

namespace Modules\Orders\UseCases;

use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\Outputs\OrderListOutput;

final class ListOrdersUseCase
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    /** @return array<int, OrderListOutput> */
    public function execute(?int $customerId = null): array
    {
        return $this->orders->all($customerId)->map(OrderListOutput::fromModel(...))->all();
    }
}
