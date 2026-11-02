<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->string('gender', 20)->nullable()->after('status');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->boolean('is_default')->default(false)->after('attributes');
        });

        Schema::table('product_translations', function (Blueprint $table): void {
            $table->string('slug')->nullable()->change();
        });

        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->unsignedInteger('low_stock_threshold')->default(10)->change();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table): void {
            $table->unsignedInteger('low_stock_threshold')->default(0)->change();
        });

        Schema::table('product_translations', function (Blueprint $table): void {
            $table->string('slug')->nullable(false)->change();
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn('is_default');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('gender');
        });
    }
};
