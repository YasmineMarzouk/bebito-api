<?php

namespace Modules\Core\UseCases;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Catalog\Models\CategoryTranslation;
use Modules\Catalog\Models\Product;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\DTOs\Outputs\DashboardOverviewOutput;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;

final readonly class GetDashboardOverviewUseCase
{
    public function __construct(
        private GetDashboardAnalyticsUseCase $analytics,
        private FileStorageServiceInterface $files,
    ) {}

    public function execute(): DashboardOverviewOutput
    {
        $now = CarbonImmutable::now();
        $analytics = $this->analytics->execute();
        $revenue30Days = $this->revenue($now->subDays(29)->startOfDay(), $now);
        $previousRevenue30Days = $this->revenue($now->subDays(59)->startOfDay(), $now->subDays(30)->endOfDay());
        $ordersToday = $this->orders($now->startOfDay(), $now);
        $ordersYesterday = $this->orders($now->subDay()->startOfDay(), $now->subDay()->endOfDay());
        $ordersThisMonth = $this->orders($now->startOfMonth(), $now);
        $ordersPreviousMonth = $this->orders($now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth());
        $newCustomers = $this->customers($now->startOfMonth(), $now);
        $newCustomersPreviousMonth = $this->customers($now->subMonth()->startOfMonth(), $now->subMonth()->endOfMonth());

        return new DashboardOverviewOutput(
            revenue30DaysAmount: $revenue30Days,
            revenue30DaysChange: $this->percentageChange($revenue30Days, $previousRevenue30Days),
            ordersToday: $ordersToday,
            ordersTodayChange: $this->percentageChange($ordersToday, $ordersYesterday),
            ordersThisMonth: $ordersThisMonth,
            ordersThisMonthChange: $this->percentageChange($ordersThisMonth, $ordersPreviousMonth),
            newCustomers: $newCustomers,
            newCustomersChange: $this->percentageChange($newCustomers, $newCustomersPreviousMonth),
            currencyCode: $analytics->currencyCode,
            decimalPlaces: $analytics->decimalPlaces,
            monthly: $analytics->monthly,
            topCategories: $this->topCategories(),
            bestSellingProducts: $this->bestSellingProducts($analytics->popularProducts),
            lowStockProducts: $this->lowStockProducts(),
            latestCustomers: $this->latestCustomers(),
            recentOrders: $this->recentOrders(),
        );
    }

    private function revenue(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return (int) ($this->validOrders()->whereBetween('created_at', [$start, $end])->sum('total_amount') ?? 0);
    }

    private function orders(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return $this->validOrders()->whereBetween('created_at', [$start, $end])->count();
    }

    private function customers(CarbonImmutable $start, CarbonImmutable $end): int
    {
        return User::query()
            ->where('user_type', UserTypeEnum::Customer->value)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    /** @return array<int, array{name: string, value: float}> */
    private function topCategories(): array
    {
        $categories = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('category_product', 'category_product.product_id', '=', 'product_variants.product_id')
            ->where('orders.status', '!=', OrderStatusEnum::CANCELLED->value)
            ->selectRaw('category_product.category_id as category_id, SUM(order_items.total_amount) as revenue_amount')
            ->groupBy('category_product.category_id')
            ->orderByDesc('revenue_amount')
            ->limit(5)
            ->get();
        $names = CategoryTranslation::query()
            ->whereIn('category_id', $categories->pluck('category_id')->all())
            ->orderByRaw("CASE WHEN locale_code = 'fr' THEN 0 ELSE 1 END")
            ->get()
            ->groupBy('category_id')
            ->map(fn (Collection $translations): string => (string) $translations->first()->name);
        $total = max(1, (int) $categories->sum('revenue_amount'));

        return $categories->map(fn (object $category): array => [
            'name' => $names->get($category->category_id, 'Category #'.$category->category_id),
            'value' => round(((int) $category->revenue_amount / $total) * 100, 1),
        ])->all();
    }

    /** @param array<int, array{id: int|null, name: string, imageUrl: string|null, soldQuantity: int, unitPriceAmount: int}> $popularProducts @return array<int, array{id: int|null, name: string, category: string|null, imageUrl: string|null, soldQuantity: int, unitPriceAmount: int}> */
    private function bestSellingProducts(array $popularProducts): array
    {
        $productIds = collect($popularProducts)->pluck('id')->filter()->map(fn (mixed $id): int => (int) $id)->all();
        $products = Product::query()
            ->with(['categories.translations'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return collect($popularProducts)->map(function (array $product) use ($products): array {
            $catalogProduct = $product['id'] === null ? null : $products->get($product['id']);
            $category = $catalogProduct?->categories->first();
            $translation = $category?->translations->firstWhere('locale_code', 'fr') ?? $category?->translations->first();

            return [...$product, 'category' => $translation?->name];
        })->all();
    }

    /** @return array<int, array{id: int, name: string, sku: string|null, imageUrl: string|null, stockQuantity: int}> */
    private function lowStockProducts(): array
    {
        return Product::query()
            ->with(['translations', 'assets'])
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get()
            ->map(function (Product $product): array {
                $translation = $product->translations->firstWhere('locale_code', 'fr') ?? $product->translations->first();
                $asset = $product->assets->firstWhere('primary_image', true) ?? $product->assets->first();

                return [
                    'id' => $product->id,
                    'name' => $translation?->name ?? 'Product #'.$product->id,
                    'sku' => $product->sku,
                    'imageUrl' => $asset === null ? null : $this->files->url($asset),
                    'stockQuantity' => $product->stock_quantity,
                ];
            })->all();
    }

    /** @return array<int, array{id: int, name: string, email: string, orderCount: int}> */
    private function latestCustomers(): array
    {
        $customers = User::query()
            ->where('user_type', UserTypeEnum::Customer->value)
            ->latest('created_at')
            ->limit(5)
            ->get(['id', 'name', 'email']);
        $orderCounts = Order::query()
            ->whereIn('user_id', $customers->pluck('id')->all())
            ->selectRaw('user_id, COUNT(*) as order_count')
            ->groupBy('user_id')
            ->pluck('order_count', 'user_id');

        return $customers->map(fn (User $customer): array => [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'orderCount' => (int) ($orderCounts->get($customer->id) ?? 0),
        ])->all();
    }

    /** @return array<int, array{id: int, number: string, customerName: string, status: int, totalAmount: int, createdAt: string}> */
    private function recentOrders(): array
    {
        return Order::query()
            ->latest('created_at')
            ->limit(6)
            ->get()
            ->map(fn (Order $order): array => [
                'id' => $order->id,
                'number' => $order->number,
                'customerName' => $order->customer_name,
                'status' => $order->status->value,
                'totalAmount' => $order->total_amount,
                'createdAt' => $order->created_at->toDateString(),
            ])->all();
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
