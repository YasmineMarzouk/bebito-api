<?php

namespace Modules\Orders\Contracts\Services;

use Modules\Orders\Contracts\ValueObjects\CustomerOrderStatistics;

interface CustomerOrderStatisticsServiceInterface
{
    public function forCustomer(int $userId): CustomerOrderStatistics;

    /** @param array<int, int> $userIds @return array<int, CustomerOrderStatistics> */
    public function forCustomers(array $userIds): array;
}
