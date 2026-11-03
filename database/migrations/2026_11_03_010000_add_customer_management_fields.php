<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_blocked')->default(false)->after('password');
        });

        Schema::table('customer_profiles', function (Blueprint $table): void {
            $table->string('manual_badge', 10)->nullable()->after('marketing_opt_in');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table): void {
            $table->dropColumn('manual_badge');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('is_blocked');
        });
    }
};
