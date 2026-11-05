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
        Schema::rename('role_user', 'user_role');
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->after('title')->constrained()->nullOnDelete();
        });
        Schema::rename('user_role', 'role_user');
    }
};
