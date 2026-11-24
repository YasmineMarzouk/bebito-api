<?php

namespace Modules\Orders\UseCases;

use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\Outputs\CustomerOrderPageOutput;

final class ListCustomerOrdersUseCase
{
    public function __construct(private readonly OrderRepositoryInterface $orders) {}

    public function execute(int $customerId, int $page): CustomerOrderPageOutput
    {
        return CustomerOrderPageOutput::fromPaginator($this->orders->paginateForCustomer($customerId, $page));
    }
}
