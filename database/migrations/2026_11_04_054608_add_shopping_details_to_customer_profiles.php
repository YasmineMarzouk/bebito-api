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
        Schema::table('users', function (Blueprint $table): void {
            $table->string('gender', 10)->nullable()->after('name');
        });

        Schema::table('customer_profiles', function (Blueprint $table): void {
            $table->string('shopping_for', 20)->default('gift')->after('manual_badge');
        });

        Schema::create('customer_children', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('birth_date');
            $table->string('gender', 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_children');

        Schema::table('customer_profiles', function (Blueprint $table): void {
            $table->dropColumn('shopping_for');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('gender');
        });
    }
};
