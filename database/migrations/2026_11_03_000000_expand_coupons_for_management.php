<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->unsignedBigInteger('fixed_amount')->nullable()->after('percentage');
            $table->unsignedBigInteger('min_order_amount')->default(0)->after('fixed_amount');
            $table->unsignedInteger('usage_limit')->nullable()->after('min_order_amount');
            $table->unsignedInteger('usage_count')->default(0)->after('usage_limit');
            $table->date('expires_at')->nullable()->after('usage_count');
        });
    }

    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table): void {
            $table->dropColumn(['fixed_amount', 'min_order_amount', 'usage_limit', 'usage_count', 'expires_at']);
        });
    }
};
