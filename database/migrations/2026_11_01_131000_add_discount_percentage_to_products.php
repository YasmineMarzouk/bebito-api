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
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('compare_at_price_amount');
        });

        DB::table('products')
            ->whereNotNull('compare_at_price_amount')
            ->where('compare_at_price_amount', '>', 0)
            ->orderBy('id')
            ->each(function (object $product): void {
                $discount = (($product->compare_at_price_amount - $product->price_amount) / $product->compare_at_price_amount) * 100;

                DB::table('products')->where('id', $product->id)->update([
                    'discount_percentage' => round(max(0, $discount), 2),
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('discount_percentage');
        });
    }
};
