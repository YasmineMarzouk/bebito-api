<?php

namespace Modules\Customer\UseCases;

use Modules\Customer\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Contracts\Services\CustomerBadgeResolverInterface;
use Modules\Customer\DTOs\Outputs\CustomerOutput;
use Modules\Core\DTOs\Outputs\PaginatedOutput;
use Modules\Orders\Contracts\Services\CustomerOrderStatisticsServiceInterface;
use Modules\Orders\Contracts\ValueObjects\CustomerOrderStatistics;

final class ListCustomersUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly CustomerOrderStatisticsServiceInterface $orders,
        private readonly CustomerBadgeResolverInterface $badges,
    ) {}

    public function execute(int $perPage, int $page): PaginatedOutput
    {
        $customers = $this->customers->paginate($perPage, $page);
        $statistics = $this->orders->forCustomers($customers->getCollection()->pluck('id')->map(fn (int $id): int => $id)->all());

        $items = $customers->getCollection()->map(function ($customer) use ($statistics): CustomerOutput {
            $profile = $this->customers->profileFor($customer->id);
            $customerStatistics = $statistics[$customer->id] ?? new CustomerOrderStatistics(0, 0, 0, 0);

            return CustomerOutput::fromModel($customer, $profile, $customerStatistics, $this->badges->resolve($customer, $profile, $customerStatistics));
        })->all();

        return new PaginatedOutput($items, $customers->currentPage(), $customers->lastPage(), $customers->perPage(), $customers->total());
    }
}
