<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->softDeletes();
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('is_default');
            $table->index(['product_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropIndex(['product_id', 'is_active']);
            $table->dropColumn('is_active');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropSoftDeletes();
        });
    }
};
