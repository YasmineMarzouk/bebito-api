<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->unsignedBigInteger('subtotal_amount')->default(0)->after('coupon_discount_amount');
            $table->unsignedBigInteger('shipping_amount')->default(0)->after('subtotal_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['subtotal_amount', 'shipping_amount']);
        });
    }
};
