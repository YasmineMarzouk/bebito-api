<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Enums\ProductGenderEnum;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Orders\Enums\OrderItemTypeEnum;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('status')->default(ProductStatusEnum::Draft->value)->index();
            $table->unsignedTinyInteger('gender')->nullable();
            $table->string('sku')->nullable()->unique();
            $table->unsignedBigInteger('price_amount')->default(0);
            $table->unsignedBigInteger('compare_at_price_amount')->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->unsignedInteger('low_stock_threshold')->default(10);
            $table->timestamp('first_published_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pack_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pack_id')->constrained()->cascadeOnDelete();
            $table->string('locale_code', 5);
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->unique(['pack_id', 'locale_code']);
            $table->unique(['locale_code', 'slug']);
        });

        Schema::create('pack_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pack_id')->constrained()->cascadeOnDelete();
            $table->string('sku')->unique();
            $table->json('attributes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['pack_id', 'is_active']);
        });

        Schema::create('pack_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pack_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->unique(['pack_id', 'product_id']);
            $table->index('product_id');
        });

        Schema::create('pack_variant_component_variants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pack_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pack_component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->timestamps();

            $table->unique(['pack_variant_id', 'pack_component_id'], 'pack_variant_component_mapping_unique');
            $table->index('product_variant_id');
        });

        Schema::create('category_pack', function (Blueprint $table): void {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pack_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['category_id', 'pack_id']);
        });

        Schema::create('collection_pack', function (Blueprint $table): void {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pack_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->primary(['collection_id', 'pack_id']);
        });

        Schema::create('pack_tag', function (Blueprint $table): void {
            $table->foreignId('pack_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['pack_id', 'tag_id']);
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('catalog_item_type', 20)->default(OrderItemTypeEnum::Product->value)->after('product_variant_id');
            $table->foreignId('pack_variant_id')->nullable()->after('catalog_item_type')->constrained()->nullOnDelete();
            $table->json('pack_snapshot')->nullable()->after('variant_attributes');
            $table->index('catalog_item_type');
        });

        Schema::create('order_item_pack_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('product_sku');
            $table->string('variant_sku');
            $table->json('variant_attributes')->nullable();
            $table->unsignedInteger('quantity_per_pack');
            $table->unsignedInteger('quantity_consumed');
            $table->unsignedBigInteger('unit_price_amount');
            $table->unsignedBigInteger('total_amount');
            $table->json('component_snapshot');
            $table->timestamps();

            $table->unique(['order_item_id', 'product_variant_id'], 'order_item_pack_component_variant_unique');
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_pack_components');

        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropIndex(['catalog_item_type']);
            $table->dropConstrainedForeignId('pack_variant_id');
            $table->dropColumn(['catalog_item_type', 'pack_snapshot']);
        });

        Schema::dropIfExists('pack_tag');
        Schema::dropIfExists('collection_pack');
        Schema::dropIfExists('category_pack');
        Schema::dropIfExists('pack_variant_component_variants');
        Schema::dropIfExists('pack_components');
        Schema::dropIfExists('pack_variants');
        Schema::dropIfExists('pack_translations');
        Schema::dropIfExists('packs');
    }
};
