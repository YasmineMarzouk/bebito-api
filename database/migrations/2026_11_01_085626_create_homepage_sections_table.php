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
        Schema::create('homepage_sections', function (Blueprint $table) {
            $table->id(); $table->string('locale_code',5); $table->string('key'); $table->string('type'); $table->json('content'); $table->unsignedSmallInteger('sort_order')->default(0); $table->boolean('is_active')->default(true); $table->unique(['locale_code','key']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_sections');
    }
};
