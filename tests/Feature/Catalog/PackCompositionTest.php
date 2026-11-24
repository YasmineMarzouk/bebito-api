<?php

namespace Tests\Feature\Catalog;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Contracts\Services\PackCompositionValidationServiceInterface;
use Modules\Catalog\DTOs\Inputs\PackComponentInput;
use Modules\Catalog\DTOs\Inputs\PackVariantInput;
use Modules\Catalog\DTOs\Inputs\PackVariantMappingInput;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Exceptions\InvalidPackCompositionException;
use Modules\Catalog\Exceptions\PackCompositionLockedException;
use Tests\Concerns\CreatesPackFixtures;
use Tests\TestCase;

class PackCompositionTest extends TestCase
{
    use CreatesPackFixtures;
    use RefreshDatabase;

    public function test_two_and_three_component_compositions_with_complete_mappings_are_valid(): void
    {
        $first = $this->createSellableVariant('CMP-FIRST', 10, 10000);
        $second = $this->createSellableVariant('CMP-SECOND', 10, 5000);
        $third = $this->createSellableVariant('CMP-THIRD', 10, 2500);
        $validator = app(PackCompositionValidationServiceInterface::class);

        $twoComponents = [
            new PackComponentInput($first->product_id, 2),
            new PackComponentInput($second->product_id, 3),
        ];
        $twoVariant = new PackVariantInput(null, [], true, true, [
            new PackVariantMappingInput($first->product_id, $first->id),
            new PackVariantMappingInput($second->product_id, $second->id),
        ]);
        $validator->validate($twoComponents, [$twoVariant], true);

        $threeComponents = [...$twoComponents, new PackComponentInput($third->product_id, 4)];
        $threeVariant = new PackVariantInput(null, [], true, true, [
            new PackVariantMappingInput($first->product_id, $first->id),
            new PackVariantMappingInput($second->product_id, $second->id),
            new PackVariantMappingInput($third->product_id, $third->id),
        ]);
        $validator->validate($threeComponents, [$threeVariant], true);

        $this->assertSame(35000, $validator->compareAtPriceAmount($twoComponents));
        $this->assertSame(45000, $validator->compareAtPriceAmount($threeComponents));
    }

    public function test_fewer_than_two_components_are_rejected(): void
    {
        $first = $this->createSellableVariant('CMP-INVALID-FIRST', 10, 10000);

        $this->expectException(InvalidPackCompositionException::class);

        app(PackCompositionValidationServiceInterface::class)->validate([
            new PackComponentInput($first->product_id, 1),
        ], [], true);
    }

    public function test_duplicate_components_are_rejected(): void
    {
        $first = $this->createSellableVariant('CMP-DUPLICATE-FIRST', 10, 10000);

        $this->expectException(InvalidPackCompositionException::class);

        app(PackCompositionValidationServiceInterface::class)->validate([
            new PackComponentInput($first->product_id, 1),
            new PackComponentInput($first->product_id, 1),
        ], [new PackVariantInput(null, [], true, true, [
            new PackVariantMappingInput($first->product_id, $first->id),
        ])], true);
    }

    public function test_zero_or_negative_component_quantity_is_rejected(): void
    {
        $first = $this->createSellableVariant('CMP-QUANTITY-FIRST', 10, 10000);
        $second = $this->createSellableVariant('CMP-QUANTITY-SECOND', 10, 5000);

        $this->expectException(InvalidPackCompositionException::class);

        app(PackCompositionValidationServiceInterface::class)->validate([
            new PackComponentInput($first->product_id, 0),
            new PackComponentInput($second->product_id, 1),
        ], [], false);
    }

    public function test_mapping_a_variant_from_another_product_is_rejected(): void
    {
        $first = $this->createSellableVariant('CMP-MAPPING-FIRST', 10, 10000);
        $second = $this->createSellableVariant('CMP-MAPPING-SECOND', 10, 5000);
        $third = $this->createSellableVariant('CMP-MAPPING-THIRD', 10, 2500);

        $this->expectException(InvalidPackCompositionException::class);

        app(PackCompositionValidationServiceInterface::class)->validate([
            new PackComponentInput($first->product_id, 1),
            new PackComponentInput($second->product_id, 1),
        ], [new PackVariantInput(null, [], true, true, [
            new PackVariantMappingInput($first->product_id, $third->id),
            new PackVariantMappingInput($second->product_id, $second->id),
        ])], true);
    }

    public function test_component_variants_are_derived_by_their_shared_size_and_color(): void
    {
        $first = $this->createSellableVariant('CMP-DERIVE-FIRST', 10, 10000);
        $second = $this->createSellableVariant('CMP-DERIVE-SECOND', 10, 5000);
        $components = [
            new PackComponentInput($first->product_id, 1),
            new PackComponentInput($second->product_id, 2),
        ];

        $variants = app(PackCompositionValidationServiceInterface::class)->deriveVariants($components);

        $this->assertCount(1, $variants);
        $this->assertSame(['size' => '3-6m'], $variants[0]->attributes);
        $this->assertSame($first->id, $variants[0]->componentMappings[0]->productVariantId);
        $this->assertSame($second->id, $variants[0]->componentMappings[1]->productVariantId);
    }

    public function test_first_publication_permanently_locks_composition_but_not_pack_catalog_fields(): void
    {
        $first = $this->createSellableVariant('PUB-FIRST', 10, 10000);
        $second = $this->createSellableVariant('PUB-SECOND', 10, 5000);
        $fixture = $this->createSellablePack('BPK-PUB', 20000, [
            ['variant' => $first, 'quantity' => 1],
            ['variant' => $second, 'quantity' => 1],
        ]);
        $pack = $fixture['pack'];
        $pack->update([
            'status' => ProductStatusEnum::Draft->value,
            'first_published_at' => now(),
        ]);
        $repository = app(PackRepositoryInterface::class);

        $repository->update($pack, ['price_amount' => 18000, 'low_stock_threshold' => 3]);

        $this->assertDatabaseHas('packs', [
            'id' => $pack->id,
            'status' => ProductStatusEnum::Draft->value,
            'price_amount' => 18000,
            'low_stock_threshold' => 3,
        ]);
        $this->expectException(PackCompositionLockedException::class);

        $repository->replaceComposition($pack, [
            new PackComponentInput($first->product_id, 2),
            new PackComponentInput($second->product_id, 1),
        ], []);
    }

    public function test_component_price_changes_refresh_compare_at_price_without_changing_manual_sell_price(): void
    {
        $first = $this->createSellableVariant('PRI-FIRST', 10, 10000);
        $second = $this->createSellableVariant('PRI-SECOND', 10, 5000);
        $fixture = $this->createSellablePack('BPK-PRI', 25000, [
            ['variant' => $first, 'quantity' => 2],
            ['variant' => $second, 'quantity' => 3],
        ]);

        $first->product->update(['price_amount' => 16000]);
        app(PackRepositoryInterface::class)->refreshCompareAtPricesForProduct($first->product_id);

        $this->assertDatabaseHas('packs', [
            'id' => $fixture['pack']->id,
            'price_amount' => 25000,
            'compare_at_price_amount' => 47000,
            'discount_percentage' => 46.81,
        ]);
    }
}
