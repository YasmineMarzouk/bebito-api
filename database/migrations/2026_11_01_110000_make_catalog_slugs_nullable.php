<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table): void {
            $table->string('slug')->nullable()->change();
        });

        Schema::table('category_translations', function (Blueprint $table): void {
            $table->string('slug')->nullable()->change();
        });

        Schema::table('collection_translations', function (Blueprint $table): void {
            $table->string('slug')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table): void {
            $table->string('slug')->nullable(false)->change();
        });

        Schema::table('category_translations', function (Blueprint $table): void {
            $table->string('slug')->nullable(false)->change();
        });

        Schema::table('collection_translations', function (Blueprint $table): void {
            $table->string('slug')->nullable(false)->change();
        });
    }
};
