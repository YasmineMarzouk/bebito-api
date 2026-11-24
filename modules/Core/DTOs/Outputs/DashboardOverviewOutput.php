<?php

namespace Modules\Core\DTOs\Outputs;

final readonly class DashboardOverviewOutput
{
    /**
     * @param  array<int, array{month: string, revenueAmount: int, orders: int}>  $monthly
     * @param  array<int, array{name: string, value: float}>  $topCategories
     * @param  array<int, array{id: int|null, name: string, category: string|null, imageUrl: string|null, soldQuantity: int, unitPriceAmount: int}>  $bestSellingProducts
     * @param  array<int, array{id: int, name: string, sku: string|null, imageUrl: string|null, stockQuantity: int}>  $lowStockProducts
     * @param  array<int, array{id: int, name: string, email: string, orderCount: int}>  $latestCustomers
     * @param  array<int, array{id: int, number: string, customerName: string, status: int, totalAmount: int, createdAt: string}>  $recentOrders
     */
    public function __construct(
        public int $revenue30DaysAmount,
        public ?float $revenue30DaysChange,
        public int $ordersToday,
        public ?float $ordersTodayChange,
        public int $ordersThisMonth,
        public ?float $ordersThisMonthChange,
        public int $newCustomers,
        public ?float $newCustomersChange,
        public string $currencyCode,
        public int $decimalPlaces,
        public array $monthly,
        public array $topCategories,
        public array $bestSellingProducts,
        public array $lowStockProducts,
        public array $latestCustomers,
        public array $recentOrders,
    ) {}
}
