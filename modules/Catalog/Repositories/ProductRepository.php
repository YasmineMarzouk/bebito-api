<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\CreateProductInput;
use Modules\Catalog\DTOs\Inputs\ProductTranslationInput;
use Modules\Catalog\DTOs\Inputs\ProductVariantInput;
use Modules\Catalog\DTOs\Inputs\UpdateProductInput;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Exceptions\InvalidProductVariantException;
use Modules\Catalog\Models\InventoryItem;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Models\Size;
use Modules\Catalog\Models\Tag;
use Modules\Core\Models\Asset;
use Modules\Inventory\Contracts\Repositories\InventoryMovementRepositoryInterface;
use Modules\Inventory\Enums\InventoryMovementReasonEnum;
use Modules\Core\Support\RichText;

final class ProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly InventoryMovementRepositoryInterface $movements,
        private readonly PackRepositoryInterface $packs,
    ) {}

    public function all(): Collection
    {
        return Product::query()->with($this->relations())->latest('id')->get();
    }

    public function paginate(int $perPage, int $page): LengthAwarePaginator
    {
        return Product::query()->with($this->relations())->latest('id')->paginate($perPage, ['*'], 'page', $page);
    }

    public function homepage(): Collection
    {
        return Product::query()
            ->with($this->publicRelations())
            ->where('status', ProductStatusEnum::Published->value)
            ->latest('id')
            ->limit(4)
            ->get();
    }

    public function shop(?int $collectionId = null, bool $discounted = false): Collection
    {
        return Product::query()
            ->with($this->publicRelations())
            ->where('status', ProductStatusEnum::Published->value)
            ->when($collectionId !== null, fn ($query) => $query->whereHas('collections', fn ($collections) => $collections->whereKey($collectionId)->where('is_active', true)))
            ->when($discounted, fn ($query) => $query->whereNotNull('discount_percentage')->where('discount_percentage', '>', 0))
            ->latest('id')
            ->get();
    }

    public function findShopByIds(array $productIds): Collection
    {
        return Product::query()
            ->with($this->publicRelations())
            ->whereKey($productIds)
            ->where('status', ProductStatusEnum::Published->value)
            ->get();
    }

    public function findShop(string $identifier): ?Product
    {
        return Product::query()
            ->with($this->publicRelations())
            ->where('status', ProductStatusEnum::Published->value)
            ->where(function ($query) use ($identifier): void {
                if (ctype_digit($identifier)) {
                    $query->whereKey((int) $identifier);

                    return;
                }

                $query->whereHas('translations', fn ($translations) => $translations->where('slug', $identifier));
            })
            ->first();
    }

    public function find(int $id): ?Product
    {
        return Product::query()->with($this->relations())->find($id);
    }

    public function findByIds(array $productIds): Collection
    {
        return Product::query()->whereKey($productIds)->get();
    }

    public function findByIdsWithVariants(array $productIds): Collection
    {
        return Product::query()->with('allVariants')->whereKey($productIds)->get();
    }

    public function findAsset(int $productId, int $assetId): ?Asset
    {
        return Asset::query()
            ->where('resource_type', 'product')
            ->where('resource_id', $productId)
            ->find($assetId);
    }

    public function setPrimaryAsset(int $productId, int $assetId): bool
    {
        return DB::transaction(function () use ($productId, $assetId): bool {
            $assets = Asset::query()
                ->where('resource_type', 'product')
                ->where('resource_id', $productId)
                ->lockForUpdate()
                ->get();
            $asset = $assets->firstWhere('id', $assetId);

            if ($asset === null) {
                return false;
            }

            Asset::query()
                ->where('resource_type', 'product')
                ->where('resource_id', $productId)
                ->update(['primary_image' => false]);
            $asset->update(['primary_image' => true]);

            return true;
        });
    }

    public function create(CreateProductInput $input, ?int $userId = null): Product
    {
        return DB::transaction(function () use ($input, $userId): Product {
            $product = Product::query()->create($this->productAttributes($input));
            $product->update(['sku' => sprintf('BEB-%06d', $product->id)]);
            $this->storeTranslations($product, $input->translations);
            $this->syncOrganization($product, $input->categoryIds, $input->collectionIds, $input->tags);

            foreach ($input->variants as $index => $variantInput) {
                $variant = $this->createVariant($product, $variantInput->attributes, $index === 0, $variantInput->stockQuantity, $variantInput->lowStockThreshold, $userId);
                $this->syncVariantPrice($variant, $input);
            }

            $product->update(['stock_quantity' => $this->activeStockQuantity($product->id)]);
            $this->packs->refreshCompareAtPricesForProduct($product->id);

            return $this->find($product->id) ?? $product;
        });
    }

    /** @param array<int, ProductTranslationInput> $translations */
    public function update(Product $product, UpdateProductInput $input, array $translations, ?int $userId = null): Product
    {
        return DB::transaction(function () use ($product, $input, $translations, $userId): Product {
            $variants = ProductVariant::query()
                ->with(['inventoryItem', 'prices'])
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $requestedVariantIds = collect($input->variants)
                ->map(fn (ProductVariantInput $variant): ?int => $variant->id)
                ->filter()
                ->values()
                ->all();

            if (count($requestedVariantIds) !== $variants->only($requestedVariantIds)->count()) {
                throw new InvalidProductVariantException;
            }

            $attributes = $this->productAttributes($input);

            $product->update($attributes);
            $this->storeTranslations($product, $translations);
            $this->syncOrganization($product, $input->categoryIds, $input->collectionIds, $input->tags);

            $activeVariantIds = [];
            foreach ($input->variants as $variantInput) {
                if ($variantInput->id !== null) {
                    /** @var ProductVariant $variant */
                    $variant = $variants->get($variantInput->id);
                    $variant->update([
                        'attributes' => $variantInput->attributes,
                        'size_id' => $this->sizeIdFor($variantInput->attributes),
                    ]);
                    $this->syncInventory($variant, $variantInput->stockQuantity, $variantInput->lowStockThreshold, $userId);
                } else {
                    $variant = $this->createVariant($product, $variantInput->attributes, false, $variantInput->stockQuantity, $variantInput->lowStockThreshold, $userId);
                }

                $this->syncVariantPrice($variant, $input);
                $activeVariantIds[] = $variant->id;
            }

            foreach ($variants->except($requestedVariantIds) as $variant) {
                $this->retireVariant($variant, $userId);
            }

            $this->setDefaultVariant($product->id, $activeVariantIds, $variants);
            $product->update(['stock_quantity' => $this->activeStockQuantity($product->id)]);
            $this->packs->refreshCompareAtPricesForProduct($product->id);

            return $this->find($product->id) ?? $product;
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    /** @return array<int, string> */
    private function relations(): array
    {
        return ['translations', 'variants.prices', 'variants.inventoryItem', 'assets', 'categories.translations', 'collections.translations', 'tags'];
    }

    /** @return array<int|string, string|\Closure> */
    private function publicRelations(): array
    {
        return [
            'translations',
            'variants.prices',
            'variants.inventoryItem',
            'assets',
            'categories' => fn ($query) => $query->where('is_active', true)->with('translations'),
            'collections' => fn ($query) => $query->where('is_active', true)->with('translations'),
            'tags',
        ];
    }

    /** @param array<int, ProductTranslationInput> $translations */
    private function storeTranslations(Product $product, array $translations): void
    {
        foreach ($translations as $translation) {
            $product->translations()->updateOrCreate(
                ['locale_code' => $translation->localeCode],
                [
                    'name' => $translation->name,
                    'slug' => $translation->slug,
                    'short_description' => RichText::sanitize($translation->shortDescription),
                    'description' => RichText::sanitize($translation->description),
                ],
            );
        }
    }

    /** @param array<int, int> $categoryIds @param array<int, int> $collectionIds @param array<int, string> $tagNames */
    private function syncOrganization(Product $product, array $categoryIds, array $collectionIds, array $tagNames): void
    {
        $product->categories()->sync($categoryIds);
        $product->collections()->sync($collectionIds);
        $tagIds = collect($tagNames)
            ->map(fn (string $name): int => Tag::query()->firstOrCreate(['name' => trim($name)])->id)
            ->all();
        $product->tags()->sync($tagIds);
    }

    /** @param array<string, string> $attributes */
    private function createVariant(Product $product, array $attributes, bool $isDefault, int $quantity, int $threshold, ?int $userId): ProductVariant
    {
        $number = $product->allVariants()->count() + 1;
        $variant = $product->allVariants()->create([
            'size_id' => $this->sizeIdFor($attributes),
            'sku' => sprintf('BEB-%06d-%02d', $product->id, $number),
            'attributes' => $attributes,
            'is_default' => $isDefault,
            'is_active' => true,
        ]);
        $inventory = $variant->inventoryItem()->create(['quantity' => $quantity, 'low_stock_threshold' => $threshold]);

        if ($quantity !== 0) {
            $this->recordInventoryMovement($inventory, $quantity, InventoryMovementReasonEnum::InitialStock, $userId, 'Initial product stock.');
        }

        return $variant;
    }

    private function syncVariantPrice(ProductVariant $variant, CreateProductInput|UpdateProductInput $input): void
    {
        $variant->prices()->updateOrCreate(
            ['currency_code' => 'TND'],
            [
                'price_amount' => $input->priceAmount,
                'compare_at_price_amount' => $this->compareAtPriceAmount($input),
            ],
        );
    }

    private function syncInventory(ProductVariant $variant, int $quantity, int $threshold, ?int $userId): void
    {
        $inventory = $variant->inventoryItem;

        if ($inventory === null) {
            $inventory = $variant->inventoryItem()->create(['quantity' => $quantity, 'low_stock_threshold' => $threshold]);
            if ($quantity !== 0) {
                $this->recordInventoryMovement($inventory, $quantity, InventoryMovementReasonEnum::InitialStock, $userId, 'Initial product stock.');
            }

            return;
        }

        $difference = $quantity - $inventory->quantity;
        $inventory->update(['quantity' => $quantity, 'low_stock_threshold' => $threshold]);

        if ($difference !== 0) {
            $this->recordInventoryMovement($inventory, $difference, InventoryMovementReasonEnum::ManualAdjustment, $userId, 'Product variant stock adjusted.');
        }
    }

    private function retireVariant(ProductVariant $variant, ?int $userId): void
    {
        $inventory = $variant->inventoryItem;
        if ($inventory !== null && $inventory->quantity !== 0) {
            $difference = -$inventory->quantity;
            $inventory->update(['quantity' => 0]);
            $this->recordInventoryMovement($inventory, $difference, InventoryMovementReasonEnum::ManualAdjustment, $userId, 'Product variant retired from the catalog.');
        }

        $variant->update(['is_active' => false, 'is_default' => false]);
    }

    /** @param array<int, int> $activeVariantIds @param Collection<int, ProductVariant> $existingVariants */
    private function setDefaultVariant(int $productId, array $activeVariantIds, Collection $existingVariants): void
    {
        $existingDefaultId = $existingVariants
            ->first(fn (ProductVariant $variant): bool => $variant->is_default && in_array($variant->id, $activeVariantIds, true))
            ?->id;
        $defaultVariantId = $existingDefaultId ?? $activeVariantIds[0];

        ProductVariant::query()
            ->where('product_id', $productId)
            ->where('is_active', true)
            ->update(['is_default' => false]);
        ProductVariant::query()->whereKey($defaultVariantId)->update(['is_default' => true]);
    }

    private function activeStockQuantity(int $productId): int
    {
        return (int) InventoryItem::query()
            ->whereHas('variant', fn ($query) => $query->where('product_id', $productId)->where('is_active', true))
            ->sum('quantity');
    }

    /** @param array<string, string> $attributes */
    private function sizeIdFor(array $attributes): ?int
    {
        $size = trim((string) ($attributes['size'] ?? ''));

        return $size === '' ? null : Size::query()->where('name', $size)->value('id');
    }

    private function recordInventoryMovement(InventoryItem $inventory, int $quantityChange, InventoryMovementReasonEnum $reason, ?int $userId, string $note): void
    {
        $this->movements->record($inventory->id, $quantityChange, $reason, $userId, note: $note);
    }

    /** @return array{status: string, gender: ?string, price_amount: int, compare_at_price_amount: ?int, discount_percentage: ?float, stock_quantity: int, low_stock_threshold: int} */
    private function productAttributes(CreateProductInput|UpdateProductInput $input): array
    {
        return [
            'status' => $input->status,
            'gender' => $input->gender,
            'price_amount' => $input->priceAmount,
            'compare_at_price_amount' => $this->compareAtPriceAmount($input),
            'discount_percentage' => $this->discountPercentage($input),
            'stock_quantity' => array_sum(array_map(fn (ProductVariantInput $variant): int => $variant->stockQuantity, $input->variants)),
            'low_stock_threshold' => $input->lowStockThreshold,
        ];
    }

    private function compareAtPriceAmount(CreateProductInput|UpdateProductInput $input): ?int
    {
        if ($input->compareAtPriceAmount !== null) {
            return $input->compareAtPriceAmount;
        }

        if ($input->discountPercentage === null || $input->discountPercentage <= 0) {
            return null;
        }

        return (int) round($input->priceAmount / (1 - ($input->discountPercentage / 100)));
    }

    private function discountPercentage(CreateProductInput|UpdateProductInput $input): ?float
    {
        $compareAtPriceAmount = $this->compareAtPriceAmount($input);

        if ($compareAtPriceAmount === null || $compareAtPriceAmount <= $input->priceAmount) {
            return null;
        }

        return round((($compareAtPriceAmount - $input->priceAmount) / $compareAtPriceAmount) * 100, 2);
    }
}
