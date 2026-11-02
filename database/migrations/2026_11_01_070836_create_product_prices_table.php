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
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete(); 
            $table->string('currency_code', 3); 
            $table->unsignedBigInteger('price_amount'); 
            $table->unsignedBigInteger('compare_at_price_amount')->nullable(); 
            $table->unique(['product_variant_id', 'currency_code']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
