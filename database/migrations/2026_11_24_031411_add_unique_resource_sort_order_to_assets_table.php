<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->unique(
                ['resource_type', 'resource_id', 'sort_order'],
                'assets_resource_sort_order_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropUnique('assets_resource_sort_order_unique');
        });
    }
};
