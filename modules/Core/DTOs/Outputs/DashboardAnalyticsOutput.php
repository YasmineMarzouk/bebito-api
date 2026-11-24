<?php

namespace Modules\Core\DTOs\Outputs;

final readonly class DashboardAnalyticsOutput
{
    /**
     * @param  array<int, array{month: string, revenueAmount: int, orders: int}>  $monthly
     * @param  array<int, array{id: int|null, name: string, imageUrl: string|null, soldQuantity: int, unitPriceAmount: int}>  $popularProducts
     */
    public function __construct(
        public int $revenueAmount,
        public int $orderCount,
        public ?float $revenueChange,
        public ?float $orderCountChange,
        public string $currencyCode,
        public int $decimalPlaces,
        public array $monthly,
        public array $popularProducts,
    ) {}
}
