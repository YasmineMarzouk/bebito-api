<?php

namespace Modules\Catalog\Services;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\Contracts\Services\PackCompositionValidationServiceInterface;
use Modules\Catalog\DTOs\Inputs\PackComponentInput;
use Modules\Catalog\DTOs\Inputs\PackVariantInput;
use Modules\Catalog\DTOs\Inputs\PackVariantMappingInput;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Exceptions\InvalidPackCompositionException;

final readonly class PackCompositionValidationService implements PackCompositionValidationServiceInterface
{
    public function __construct(private ProductRepositoryInterface $products) {}

    public function validate(array $components, array $variants, bool $publishing): void
    {
        $products = $this->eligibleProducts($components);

        $productVariants = $products->flatMap(fn ($product) => $product->allVariants)->keyBy('id');
        $componentProductIds = collect($components)->pluck('productId')->sort()->values()->all();
        if (collect($variants)->where('isDefault', true)->count() > 1) {
            throw new InvalidPackCompositionException;
        }
        foreach ($variants as $variant) {
            if (! $variant->isActive || count($variant->componentMappings) !== count($components)) {
                throw new InvalidPackCompositionException;
            }

            $mappedProductIds = collect($variant->componentMappings)->pluck('productId');
            if ($mappedProductIds->unique()->count() !== count($components)) {
                throw new InvalidPackCompositionException;
            }
            if ($mappedProductIds->sort()->values()->all() !== $componentProductIds) {
                throw new InvalidPackCompositionException;
            }

            foreach ($variant->componentMappings as $mapping) {
                $productVariant = $productVariants->get($mapping->productVariantId);
                if ($productVariant === null || ! $productVariant->is_active || $productVariant->product_id !== $mapping->productId) {
                    throw new InvalidPackCompositionException;
                }
            }
        }

        if ($publishing && $variants === []) {
            throw new InvalidPackCompositionException;
        }
    }

    public function deriveVariants(array $components): array
    {
        $products = $this->eligibleProducts($components);
        $variantsByProduct = $products
            ->mapWithKeys(function ($product): array {
                $variants = $product->allVariants
                    ->filter(fn ($variant): bool => $variant->is_active)
                    ->groupBy(fn ($variant): string => $this->variantKey($variant->attributes ?? []));

                return [$product->id => $variants];
            })
            ->all();
        $sharedKeys = array_keys(($variantsByProduct[array_key_first($variantsByProduct)] ?? collect())->all());
        foreach ($variantsByProduct as $variants) {
            $sharedKeys = array_values(array_intersect($sharedKeys, array_keys($variants->all())));
        }

        return array_map(function (string $key, int $position) use ($components, $variantsByProduct): PackVariantInput {
            $firstVariant = $variantsByProduct[$components[0]->productId][$key]->sortBy('id')->first();

            return new PackVariantInput(
                id: null,
                attributes: array_filter([
                    'size' => $firstVariant->attributes['size'] ?? null,
                    'color' => $firstVariant->attributes['color'] ?? null,
                ]),
                isDefault: $position === 0,
                isActive: true,
                componentMappings: array_map(
                    fn (PackComponentInput $component): PackVariantMappingInput => new PackVariantMappingInput(
                        $component->productId,
                        $variantsByProduct[$component->productId][$key]->sortBy('id')->first()->id,
                    ),
                    $components,
                ),
            );
        }, $sharedKeys, array_keys($sharedKeys));
    }

    public function compareAtPriceAmount(array $components): int
    {
        $products = $this->products->findByIds(collect($components)->pluck('productId')->all())->keyBy('id');

        if ($products->count() !== count($components)) {
            throw new InvalidPackCompositionException;
        }

        return collect($components)->sum(fn (PackComponentInput $component): int => $products->get($component->productId)->price_amount * $component->quantity);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, \Modules\Catalog\Models\Product> */
    private function eligibleProducts(array $components): \Illuminate\Database\Eloquent\Collection
    {
        if (count($components) < 2 || count($components) !== count(collect($components)->pluck('productId')->unique())) {
            throw new InvalidPackCompositionException;
        }

        if (collect($components)->contains(fn (PackComponentInput $component): bool => $component->quantity < 1)) {
            throw new InvalidPackCompositionException;
        }

        $products = $this->products->findByIdsWithVariants(collect($components)->pluck('productId')->all());
        if ($products->count() !== count($components)
            || $products->contains(fn ($product): bool => $product->status !== ProductStatusEnum::Published)) {
            throw new InvalidPackCompositionException;
        }

        return $products->keyBy('id');
    }

    /** @param array<string, mixed> $attributes */
    private function variantKey(array $attributes): string
    {
        return mb_strtolower(trim((string) ($attributes['size'] ?? ''))).'|'.mb_strtolower(trim((string) ($attributes['color'] ?? '')));
    }
}
