<?php

namespace Modules\Orders\UseCases;

use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\Outputs\OrderListOutput;
use Modules\Core\DTOs\Outputs\PaginatedOutput;

final class ListOrdersUseCase
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function execute(?int $customerId, int $perPage, int $page): PaginatedOutput
    {
        $orders = $this->orders->paginateForAdmin($customerId, $perPage, $page);

        return new PaginatedOutput($orders->getCollection()->map(OrderListOutput::fromModel(...))->all(), $orders->currentPage(), $orders->lastPage(), $orders->perPage(), $orders->total());
    }
}
