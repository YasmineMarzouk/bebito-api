<?php

namespace Modules\Core\UseCases;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Contracts\Repositories\SettingsRepositoryInterface;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\DTOs\Outputs\DashboardAnalyticsOutput;
use Modules\Core\Models\Asset;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;

final readonly class GetDashboardAnalyticsUseCase
{
    public function __construct(
        private SettingsRepositoryInterface $settings,
        private FileStorageServiceInterface $files,
    ) {}

    public function execute(): DashboardAnalyticsOutput
    {
        $now = CarbonImmutable::now();
        $currentPeriodStart = $now->startOfYear();
        $previousPeriodStart = $currentPeriodStart->subYear();
        $previousPeriodEnd = $now->subYear();
        $currentTotals = $this->totals($currentPeriodStart, $now);
        $previousTotals = $this->totals($previousPeriodStart, $previousPeriodEnd);
        $currency = $this->currency();

        return new DashboardAnalyticsOutput(
            revenueAmount: $currentTotals['revenueAmount'],
            orderCount: $currentTotals['orderCount'],
            revenueChange: $this->percentageChange($currentTotals['revenueAmount'], $previousTotals['revenueAmount']),
            orderCountChange: $this->percentageChange($currentTotals['orderCount'], $previousTotals['orderCount']),
            currencyCode: $currency['code'],
            decimalPlaces: $currency['decimalPlaces'],
            monthly: $this->monthly($now),
            popularProducts: $this->popularProducts(),
        );
    }

    /** @return array{revenueAmount: int, orderCount: int} */
    private function totals(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $totals = $this->validOrders()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue_amount, COUNT(*) as order_count')
            ->first();

        return [
            'revenueAmount' => (int) ($totals?->revenue_amount ?? 0),
            'orderCount' => (int) ($totals?->order_count ?? 0),
        ];
    }

    /** @return array<int, array{month: string, revenueAmount: int, orders: int}> */
    private function monthly(CarbonImmutable $now): array
    {
        $months = collect(range(6, 0))
            ->map(fn (int $offset): CarbonImmutable => $now->subMonths($offset)->startOfMonth())
            ->keyBy(fn (CarbonImmutable $month): string => $month->format('Y-m'));
        $totals = $this->validOrders()
            ->whereBetween('created_at', [$months->first(), $now])
            ->get(['created_at', 'total_amount'])
            ->groupBy(fn (Order $order): string => $order->created_at->format('Y-m'));

        return $months->map(function (CarbonImmutable $month, string $key) use ($totals): array {
            /** @var Collection<int, Order> $orders */
            $orders = $totals->get($key, collect());

            return [
                'month' => $month->format('M'),
                'revenueAmount' => (int) $orders->sum('total_amount'),
                'orders' => $orders->count(),
            ];
        })->values()->all();
    }

    /** @return array<int, array{id: int|null, name: string, imageUrl: string|null, soldQuantity: int, unitPriceAmount: int}> */
    private function popularProducts(): array
    {
        $products = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->where('orders.status', '!=', OrderStatusEnum::CANCELLED->value)
            ->selectRaw('product_variants.product_id as product_id, order_items.product_name as product_name, SUM(order_items.quantity) as sold_quantity, MIN(order_items.unit_price_amount) as unit_price_amount')
            ->groupBy('product_variants.product_id', 'order_items.product_name')
            ->orderByDesc('sold_quantity')
            ->limit(6)
            ->get();
        $productIds = $products->pluck('product_id')->filter()->map(fn (mixed $id): int => (int) $id)->all();
        $images = Asset::query()
            ->where('resource_type', 'product')
            ->whereIn('resource_id', $productIds)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('resource_id')
            ->map(fn (Collection $assets): Asset => $assets->first());

        return $products->map(function (object $product) use ($images): array {
            $productId = $product->product_id === null ? null : (int) $product->product_id;
            $image = $productId === null ? null : $images->get($productId);

            return [
                'id' => $productId,
                'name' => (string) $product->product_name,
                'imageUrl' => $image instanceof Asset ? $this->files->url($image) : null,
                'soldQuantity' => (int) $product->sold_quantity,
                'unitPriceAmount' => (int) $product->unit_price_amount,
            ];
        })->all();
    }

    /** @return array{code: string, decimalPlaces: int} */
    private function currency(): array
    {
        $settings = $this->settings->values();
        $currencyCode = (string) ($settings['store.default_currency'] ?? 'TND');
        $currency = collect($this->settings->activeCurrencies())->firstWhere('code', $currencyCode);

        return [
            'code' => $currencyCode,
            'decimalPlaces' => (int) ($currency['decimalPlaces'] ?? 3),
        ];
    }

    private function percentageChange(int $current, int $previous): ?float
    {
        if ($previous === 0) {
            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /** @return Builder<Order> */
    private function validOrders(): Builder
    {
        return Order::query()->where('status', '!=', OrderStatusEnum::CANCELLED->value);
    }
}
