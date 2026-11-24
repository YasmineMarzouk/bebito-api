<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\PackComponentInput;
use Modules\Catalog\DTOs\Inputs\PackVariantInput;
use Modules\Catalog\DTOs\Inputs\ProductTranslationInput;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Models\Pack;
use Modules\Catalog\Models\PackComponent;
use Modules\Catalog\Models\PackVariant;
use Modules\Catalog\Models\Tag;
use Modules\Catalog\Exceptions\PackCompositionLockedException;
use Modules\Core\Models\Asset;
use Modules\Core\Support\RichText;

final class PackRepository implements PackRepositoryInterface
{
    public function all(): Collection
    {
        return Pack::query()->with($this->relations())->latest('id')->get();
    }

    public function paginate(int $perPage, int $page, ?string $search = null): LengthAwarePaginator
    {
        return Pack::query()
            ->with($this->relations())
            ->when($search !== null && $search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $packs) use ($search): void {
                    $packs
                        ->where('sku', 'like', '%'.$search.'%')
                        ->orWhereHas('translations', fn (Builder $translations): Builder => $translations->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function shop(): Collection
    {
        return Pack::query()
            ->with($this->publicRelations())
            ->where('status', ProductStatusEnum::Published->value)
            ->latest('id')
            ->get();
    }

    public function findShopByIds(array $packIds): Collection
    {
        return Pack::query()
            ->with($this->publicRelations())
            ->whereKey($packIds)
            ->where('status', ProductStatusEnum::Published->value)
            ->get();
    }

    public function find(int $id): ?Pack
    {
        return Pack::query()->with($this->relations())->find($id);
    }

    public function findForUpdate(int $id): ?Pack
    {
        return Pack::query()->with($this->relations())->lockForUpdate()->find($id);
    }

    public function findShop(string $identifier): ?Pack
    {
        return Pack::query()
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

    public function create(array $attributes): Pack
    {
        return Pack::query()->create($attributes);
    }

    public function update(Pack $pack, array $attributes): Pack
    {
        $pack->update($attributes);

        return $pack->refresh();
    }

    public function syncTranslations(Pack $pack, array $translations): void
    {
        foreach ($translations as $translation) {
            $pack->translations()->updateOrCreate(
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

    public function syncOrganization(Pack $pack, array $categoryIds, array $collectionIds, array $tags): void
    {
        $pack->categories()->sync($categoryIds);
        $pack->collections()->sync($collectionIds);
        $tagIds = collect($tags)
            ->map(fn (string $tag): int => Tag::query()->firstOrCreate(['name' => trim($tag)])->id)
            ->all();
        $pack->tags()->sync($tagIds);
    }

    public function replaceComposition(Pack $pack, array $components, array $variants): void
    {
        if ($pack->first_published_at !== null) {
            throw new PackCompositionLockedException;
        }

        $pack->allVariants()->delete();
        $pack->components()->delete();

        $componentsByProduct = [];
        foreach ($components as $component) {
            $storedComponent = $pack->components()->create([
                'product_id' => $component->productId,
                'quantity' => $component->quantity,
            ]);
            $componentsByProduct[$component->productId] = $storedComponent;
        }

        foreach ($variants as $position => $variant) {
            $storedVariant = $pack->allVariants()->create([
                'sku' => sprintf('BPK-%06d-%02d', $pack->id, $position + 1),
                'attributes' => $variant->attributes,
                'is_default' => $variant->isDefault || ($position === 0 && ! collect($variants)->contains(fn (PackVariantInput $item): bool => $item->isDefault)),
                'is_active' => $variant->isActive,
            ]);

            foreach ($variant->componentMappings as $mapping) {
                /** @var PackComponent $component */
                $component = $componentsByProduct[$mapping->productId];
                $storedVariant->componentVariantMappings()->create([
                    'pack_component_id' => $component->id,
                    'product_variant_id' => $mapping->productVariantId,
                ]);
            }
        }
    }

    public function markFirstPublished(Pack $pack): Pack
    {
        $pack->update(['first_published_at' => $pack->first_published_at ?? now()]);

        return $pack->refresh();
    }

    public function refreshCompareAtPricesForProduct(int $productId): void
    {
        Pack::query()
            ->whereHas('components', fn ($components) => $components->where('product_id', $productId))
            ->with('components.product')
            ->get()
            ->each(function (Pack $pack): void {
                $compareAtPriceAmount = (int) $pack->components->sum(
                    fn (PackComponent $component): int => $component->quantity * $component->product->price_amount,
                );
                $discountPercentage = $compareAtPriceAmount > $pack->price_amount
                    ? round((($compareAtPriceAmount - $pack->price_amount) / $compareAtPriceAmount) * 100, 2)
                    : null;
                $pack->update([
                    'compare_at_price_amount' => $compareAtPriceAmount,
                    'discount_percentage' => $discountPercentage,
                ]);
            });
    }

    public function findAsset(int $packId, int $assetId): ?Asset
    {
        return Asset::query()
            ->where('resource_type', 'pack')
            ->where('resource_id', $packId)
            ->find($assetId);
    }

    public function setPrimaryAsset(int $packId, int $assetId): bool
    {
        return DB::transaction(function () use ($packId, $assetId): bool {
            $assets = Asset::query()
                ->where('resource_type', 'pack')
                ->where('resource_id', $packId)
                ->lockForUpdate()
                ->get();
            $asset = $assets->firstWhere('id', $assetId);
            if ($asset === null) {
                return false;
            }

            Asset::query()
                ->where('resource_type', 'pack')
                ->where('resource_id', $packId)
                ->update(['primary_image' => false]);
            $asset->update(['primary_image' => true]);

            return true;
        });
    }

    public function delete(Pack $pack): void
    {
        $pack->delete();
    }

    /** @return array<int, string> */
    private function relations(): array
    {
        return ['translations', 'variants.componentVariantMappings.productVariant.inventoryItem', 'variants.componentVariantMappings.productVariant.product', 'variants.componentVariantMappings.packComponent', 'components.product.translations', 'assets', 'categories.translations', 'collections.translations', 'tags'];
    }

    /** @return array<int|string, string|\Closure> */
    private function publicRelations(): array
    {
        return [
            'translations',
            'variants.componentVariantMappings.productVariant.inventoryItem',
            'variants.componentVariantMappings.productVariant.product',
            'variants.componentVariantMappings.packComponent',
            'components.product.translations',
            'assets',
            'categories' => fn ($query) => $query->where('is_active', true)->with('translations'),
            'collections' => fn ($query) => $query->where('is_active', true)->with('translations'),
            'tags',
        ];
    }
}
