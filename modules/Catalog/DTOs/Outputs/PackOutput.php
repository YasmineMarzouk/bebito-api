<?php

namespace Modules\Catalog\DTOs\Outputs;

use Modules\Catalog\Models\Pack;

final readonly class PackOutput
{
    /** @param array<int, array<string, mixed>> $translations @param array<int, array<string, mixed>> $variants @param array<int, array<string, mixed>> $images @param array<int, array<string, mixed>> $components */
    public function __construct(public int $id, public int $status, public ?int $gender, public ?string $sku, public int $priceAmount, public ?int $compareAtPriceAmount, public ?float $discountPercentage, public int $lowStockThreshold, public ?string $firstPublishedAt, public int $stockQuantity, public int $inventoryStatus, public array $translations, public array $variants, public array $components, public array $images, public ?int $primaryImageId, public array $categories, public array $collections, public array $tags) {}

    /** @param array{stockQuantity: int, inventoryStatus: int, variants: array<int, array{id: int, stockQuantity: int, available: bool}>} $availability */
    public static function fromModel(Pack $pack, array $availability): self
    {
        $availabilityByVariant = collect($availability['variants'])->keyBy('id');
        $primaryAsset = $pack->assets->firstWhere('primary_image', true);

        return new self($pack->id, $pack->status->value, $pack->gender?->value, $pack->sku, $pack->price_amount, $pack->compare_at_price_amount, $pack->discount_percentage, $pack->low_stock_threshold, $pack->first_published_at?->toISOString(), $availability['stockQuantity'], $availability['inventoryStatus'], $pack->translations->map(fn ($item): array => ['localeCode' => $item->locale_code, 'name' => $item->name, 'slug' => $item->slug, 'shortDescription' => $item->short_description, 'description' => $item->description])->all(), $pack->variants->map(fn ($item): array => ['id' => $item->id, 'sku' => $item->sku, 'attributes' => $item->attributes, 'isDefault' => $item->is_default, 'isActive' => $item->is_active, 'stockQuantity' => $availabilityByVariant->get($item->id)['stockQuantity'] ?? 0, 'available' => $availabilityByVariant->get($item->id)['available'] ?? false, 'componentMappings' => $item->componentVariantMappings->map(fn ($mapping): array => ['productId' => $mapping->packComponent->product_id, 'productVariantId' => $mapping->product_variant_id])->values()->all()])->values()->all(), $pack->components->map(fn ($component): array => ['id' => $component->id, 'productId' => $component->product_id, 'productName' => $component->product?->translations->firstWhere('locale_code', 'fr')?->name ?? $component->product?->sku ?? 'Unnamed product', 'quantity' => $component->quantity])->values()->all(), $pack->assets->map(fn ($asset): array => ['id' => $asset->id, 'url' => rtrim((string) config('app.url'), '/').'/storage/'.ltrim($asset->path, '/'), 'sortOrder' => $asset->sort_order])->all(), $primaryAsset?->id, $pack->categories->map(fn ($item): array => ['id' => $item->id, 'name' => $item->translations->firstWhere('locale_code', 'fr')?->name ?? ''])->all(), $pack->collections->map(fn ($item): array => ['id' => $item->id, 'name' => $item->translations->firstWhere('locale_code', 'fr')?->name ?? ''])->all(), $pack->tags->pluck('name')->all());
    }
}
