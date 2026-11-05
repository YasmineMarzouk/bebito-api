<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Enums\UserTypeEnum;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('user_type')->default('customer')->after('email');
        });

        DB::table('users')->whereIn('id', function ($query): void {
            $query->select('role_user.user_id')
                ->from('role_user')
                ->join('roles', 'roles.id', '=', 'role_user.role_id')
                ->where('roles.name', '!=', 'customer');
        })->update(['user_type' => UserTypeEnum::Staff->value]);

        Schema::table('roles', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('name');
            $table->foreignId('user_id')->nullable()->after('title')->constrained()->nullOnDelete();
        });

        DB::table('roles')->update(['title' => DB::raw('name')]);
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('title')->nullable(false)->change();
        });

        Schema::table('role_user', function (Blueprint $table): void {
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('role_user', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['user_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn('title');
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('user_type');
        });
    }
};
