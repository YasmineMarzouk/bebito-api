<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $hasLegacyPrimaryImage = Schema::hasColumn('assets', 'is_primary');

        Schema::table('assets', function (Blueprint $table) use ($hasLegacyPrimaryImage): void {
            $primaryImage = $table->boolean('primary_image')->nullable();

            if ($hasLegacyPrimaryImage) {
                $primaryImage->after('is_primary');
            }
        });

        if ($hasLegacyPrimaryImage) {
            DB::table('assets')->update(['primary_image' => DB::raw('is_primary')]);
        }

        DB::table('assets')
            ->select('resource_type', 'resource_id')
            ->distinct()
            ->orderBy('resource_type')
            ->orderBy('resource_id')
            ->each(function (object $resource): void {
                $assets = DB::table('assets')
                    ->where('resource_type', $resource->resource_type)
                    ->where('resource_id', $resource->resource_id)
                    ->orderByDesc('primary_image')
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->pluck('id');

                $primaryAssetId = $assets->first();
                if ($primaryAssetId === null) {
                    return;
                }

                DB::table('assets')
                    ->where('resource_type', $resource->resource_type)
                    ->where('resource_id', $resource->resource_id)
                    ->update(['primary_image' => false]);

                DB::table('assets')->where('id', $primaryAssetId)->update(['primary_image' => true]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table): void {
            $table->dropColumn('primary_image');
        });
    }
};
