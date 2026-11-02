<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('sku')->nullable()->unique()->after('gender');
            $table->unsignedBigInteger('price_amount')->default(0)->after('sku');
            $table->unsignedBigInteger('compare_at_price_amount')->nullable()->after('price_amount');
            $table->unsignedInteger('stock_quantity')->default(0)->after('compare_at_price_amount');
            $table->unsignedInteger('low_stock_threshold')->default(10)->after('stock_quantity');
        });

        DB::table('products')->orderBy('id')->each(function (object $product): void {
            $firstVariant = DB::table('product_variants')
                ->where('product_id', $product->id)
                ->orderBy('id')
                ->first();
            $price = $firstVariant === null ? null : DB::table('product_prices')
                ->where('product_variant_id', $firstVariant->id)
                ->where('currency_code', 'TND')
                ->first();
            $inventory = DB::table('inventory_items')
                ->join('product_variants', 'product_variants.id', '=', 'inventory_items.product_variant_id')
                ->where('product_variants.product_id', $product->id)
                ->selectRaw('COALESCE(SUM(inventory_items.quantity), 0) as total_quantity, MIN(inventory_items.low_stock_threshold) as low_stock_threshold')
                ->first();

            DB::table('products')->where('id', $product->id)->update([
                'sku' => sprintf('BEB-%06d', $product->id),
                'price_amount' => $price?->price_amount ?? 0,
                'compare_at_price_amount' => $price?->compare_at_price_amount,
                'stock_quantity' => (int) ($inventory?->total_quantity ?? 0),
                'low_stock_threshold' => (int) ($inventory?->low_stock_threshold ?? 10),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique(['sku']);
            $table->dropColumn(['sku', 'price_amount', 'compare_at_price_amount', 'stock_quantity', 'low_stock_threshold']);
        });
    }
};
