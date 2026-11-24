<?php

namespace Tests\Feature\Catalog;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Models\Pack;
use Modules\Catalog\Models\PackComponent;
use Modules\Catalog\Models\PackVariant;
use Modules\Catalog\Models\PackVariantComponentVariant;
use Modules\Orders\Enums\OrderItemTypeEnum;
use Modules\Orders\Models\OrderItem;
use Modules\Orders\Models\OrderItemPackComponent;
use Tests\TestCase;

class PackSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_pack_schema_has_the_required_columns_foreign_keys_and_indexes(): void
    {
        foreach ([
            'packs',
            'pack_translations',
            'pack_variants',
            'pack_components',
            'pack_variant_component_variants',
            'category_pack',
            'collection_pack',
            'pack_tag',
            'order_item_pack_components',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "The {$table} table must exist.");
        }

        $this->assertTrue(Schema::hasColumns('packs', [
            'status',
            'sku',
            'price_amount',
            'compare_at_price_amount',
            'discount_percentage',
            'low_stock_threshold',
            'first_published_at',
            'deleted_at',
        ]));
        $this->assertTrue(Schema::hasColumns('pack_components', ['pack_id', 'product_id', 'quantity']));
        $this->assertTrue(Schema::hasColumns('pack_variant_component_variants', ['pack_variant_id', 'pack_component_id', 'product_variant_id']));
        $this->assertTrue(Schema::hasColumns('order_items', ['catalog_item_type', 'pack_variant_id', 'pack_snapshot']));
        $this->assertTrue(Schema::hasColumns('order_item_pack_components', [
            'order_item_id',
            'product_id',
            'product_variant_id',
            'quantity_per_pack',
            'quantity_consumed',
            'unit_price_amount',
            'total_amount',
            'component_snapshot',
        ]));

        $this->assertForeignKey('pack_components', 'pack_id', 'packs', 'cascade');
        $this->assertForeignKey('pack_components', 'product_id', 'products', 'restrict');
        $this->assertForeignKey('pack_variant_component_variants', 'pack_variant_id', 'pack_variants', 'cascade');
        $this->assertForeignKey('pack_variant_component_variants', 'pack_component_id', 'pack_components', 'cascade');
        $this->assertForeignKey('pack_variant_component_variants', 'product_variant_id', 'product_variants', 'restrict');
        $this->assertForeignKey('order_items', 'pack_variant_id', 'pack_variants', 'set null');
        $this->assertForeignKey('order_item_pack_components', 'order_item_id', 'order_items', 'cascade');
        $this->assertForeignKey('order_item_pack_components', 'product_id', 'products', 'set null');
        $this->assertForeignKey('order_item_pack_components', 'product_variant_id', 'product_variants', 'set null');

        $this->assertTrue(Schema::hasIndex('packs', ['sku'], 'unique'));
        $this->assertTrue(Schema::hasIndex('pack_translations', ['pack_id', 'locale_code'], 'unique'));
        $this->assertTrue(Schema::hasIndex('pack_variants', ['sku'], 'unique'));
        $this->assertTrue(Schema::hasIndex('pack_components', ['pack_id', 'product_id'], 'unique'));
        $this->assertTrue(Schema::hasIndex('pack_variant_component_variants', 'pack_variant_component_mapping_unique', 'unique'));
        $this->assertTrue(Schema::hasIndex('category_pack', ['category_id', 'pack_id'], 'primary'));
        $this->assertTrue(Schema::hasIndex('collection_pack', ['collection_id', 'pack_id'], 'primary'));
        $this->assertTrue(Schema::hasIndex('pack_tag', ['pack_id', 'tag_id'], 'primary'));
        $this->assertTrue(Schema::hasIndex('order_item_pack_components', 'order_item_pack_component_variant_unique', 'unique'));
    }

    public function test_pack_and_order_item_models_expose_catalog_relationships_and_snapshot_casts(): void
    {
        $pack = new Pack([
            'status' => ProductStatusEnum::Draft->value,
            'first_published_at' => '2026-11-15 12:00:00',
        ]);
        $orderItem = new OrderItem([
            'catalog_item_type' => OrderItemTypeEnum::Pack->value,
            'pack_snapshot' => ['pack_id' => 123, 'sku' => 'BPK-000123'],
        ]);
        $componentSnapshot = new OrderItemPackComponent([
            'variant_attributes' => ['size' => '3-6m'],
            'quantity_per_pack' => 2,
            'quantity_consumed' => 4,
            'unit_price_amount' => 15000,
            'total_amount' => 60000,
            'component_snapshot' => ['product_id' => 10, 'product_variant_id' => 25],
        ]);

        $this->assertInstanceOf(ProductStatusEnum::class, $pack->status);
        $this->assertNotNull($pack->first_published_at);
        $this->assertInstanceOf(HasMany::class, $pack->translations());
        $this->assertInstanceOf(HasMany::class, $pack->allVariants());
        $this->assertInstanceOf(HasMany::class, $pack->components());
        $this->assertInstanceOf(BelongsToMany::class, $pack->categories());
        $this->assertInstanceOf(BelongsToMany::class, $pack->collections());
        $this->assertInstanceOf(BelongsToMany::class, $pack->tags());
        $this->assertInstanceOf(MorphMany::class, $pack->assets());
        $this->assertSame('pack', $pack->getMorphClass());
        $this->assertInstanceOf(BelongsTo::class, (new PackComponent)->product());
        $this->assertInstanceOf(BelongsTo::class, (new PackVariant)->pack());
        $this->assertInstanceOf(BelongsTo::class, (new PackVariantComponentVariant)->productVariant());

        $this->assertSame(OrderItemTypeEnum::Pack, $orderItem->catalog_item_type);
        $this->assertSame(['pack_id' => 123, 'sku' => 'BPK-000123'], $orderItem->pack_snapshot);
        $this->assertInstanceOf(BelongsTo::class, $orderItem->packVariant());
        $this->assertInstanceOf(HasMany::class, $orderItem->packComponents());
        $this->assertSame(['size' => '3-6m'], $componentSnapshot->variant_attributes);
        $this->assertSame(2, $componentSnapshot->quantity_per_pack);
        $this->assertSame(4, $componentSnapshot->quantity_consumed);
        $this->assertSame(['product_id' => 10, 'product_variant_id' => 25], $componentSnapshot->component_snapshot);
    }

    public function test_pack_migration_rolls_back_and_reapplies_without_orphaned_order_columns(): void
    {
        $migration = require database_path('migrations/2026_11_15_000000_create_pack_catalog_schema.php');

        $migration->down();

        $this->assertFalse(Schema::hasTable('packs'));
        $this->assertFalse(Schema::hasTable('order_item_pack_components'));
        $this->assertFalse(Schema::hasColumn('order_items', 'pack_variant_id'));
        $this->assertFalse(Schema::hasColumn('order_items', 'pack_snapshot'));

        $migration->up();

        $this->assertTrue(Schema::hasTable('packs'));
        $this->assertTrue(Schema::hasTable('order_item_pack_components'));
        $this->assertTrue(Schema::hasColumns('order_items', ['catalog_item_type', 'pack_variant_id', 'pack_snapshot']));
    }

    private function assertForeignKey(string $table, string $column, string $foreignTable, string $onDelete): void
    {
        foreach (Schema::getForeignKeys($table) as $definition) {
            if ($definition['columns'] !== [$column] || $definition['foreign_table'] !== $foreignTable) {
                continue;
            }

            $this->assertSame($onDelete, strtolower((string) $definition['on_delete']));

            return;
        }

        $this->fail("The {$table}.{$column} foreign key must reference {$foreignTable}.");
    }
}
