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
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); $table->string('number')->unique(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); $table->string('customer_name'); $table->string('customer_email'); $table->string('currency_code', 3)->default('TND'); $table->string('status')->default('pending'); $table->string('payment_method_code')->default('cash_on_delivery'); $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete(); $table->string('coupon_code')->nullable(); $table->unsignedBigInteger('coupon_discount_amount')->default(0); $table->unsignedBigInteger('total_amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
