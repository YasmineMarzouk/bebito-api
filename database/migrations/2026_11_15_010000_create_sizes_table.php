<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sizes', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->unsignedTinyInteger('sort_order')->unique();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->foreignId('size_id')
                ->nullable()
                ->after('product_id')
                ->constrained()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('size_id');
        });

        Schema::dropIfExists('sizes');
    }
};
