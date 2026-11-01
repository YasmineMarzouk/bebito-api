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
        Schema::create('collection_translations', function (Blueprint $table) {
            $table->id(); $table->foreignId('collection_id')->constrained()->cascadeOnDelete(); $table->string('locale_code',5); $table->string('name'); $table->string('slug'); $table->text('description')->nullable(); $table->unique(['collection_id','locale_code']); $table->unique(['locale_code','slug']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_translations');
    }
};
