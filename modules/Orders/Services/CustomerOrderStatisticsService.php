<?php

namespace Modules\Orders\Services;

use Modules\Orders\Contracts\Services\CustomerOrderStatisticsServiceInterface;
use Modules\Orders\Contracts\ValueObjects\CustomerOrderStatistics;
use Modules\Orders\Models\Order;

final class CustomerOrderStatisticsService implements CustomerOrderStatisticsServiceInterface
{
    public function forCustomer(int $userId): CustomerOrderStatistics
    {
        return $this->forCustomers([$userId])[$userId] ?? new CustomerOrderStatistics(0, 0, 0, 0);
    }

    public function forCustomers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $statistics = Order::query()
            ->whereIn('user_id', $userIds)
            ->selectRaw("user_id, COUNT(*) as order_count, SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_order_count, SUM(CASE WHEN status = 'not_picked_up' THEN 1 ELSE 0 END) as not_picked_up_order_count, COALESCE(SUM(total_amount), 0) as total_spent_amount")
            ->groupBy('user_id')
            ->get();

        return $statistics->mapWithKeys(fn (Order $order): array => [(int) $order->user_id => new CustomerOrderStatistics(
            (int) $order->order_count,
            (int) $order->delivered_order_count,
            (int) $order->not_picked_up_order_count,
            (int) $order->total_spent_amount,
        )])->all();
    }
}
