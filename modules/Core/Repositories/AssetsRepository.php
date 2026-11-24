<?php

namespace Modules\Core\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\Repositories\AssetsRepositoryInterface;
use Modules\Core\Models\Asset;

final class AssetsRepository implements AssetsRepositoryInterface
{
    public function create(array $attributes): Asset
    {
        return DB::transaction(function () use ($attributes): Asset {
            $assets = Asset::query()
                ->where('resource_type', $attributes['resource_type'])
                ->where('resource_id', $attributes['resource_id'])
                ->lockForUpdate()
                ->get();
            $hasPrimaryImage = $assets->contains(fn (Asset $asset): bool => $asset->primary_image);
            $attributes['primary_image'] = (bool) ($attributes['primary_image'] ?? false) || ! $hasPrimaryImage;

            if ($attributes['primary_image']) {
                Asset::query()
                    ->where('resource_type', $attributes['resource_type'])
                    ->where('resource_id', $attributes['resource_id'])
                    ->update(['primary_image' => false]);
            }

            return Asset::query()->create($attributes);
        });
    }

    public function delete(Asset $asset): void
    {
        DB::transaction(function () use ($asset): void {
            $resourceType = $asset->resource_type;
            $resourceId = $asset->resource_id;
            $assets = Asset::query()
                ->where('resource_type', $resourceType)
                ->where('resource_id', $resourceId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $storedAsset = $assets->firstWhere('id', $asset->id);
            if ($storedAsset === null) {
                return;
            }

            $wasPrimaryImage = $storedAsset->primary_image;

            $storedAsset->delete();

            if (! $wasPrimaryImage) {
                return;
            }

            $assets->first(fn (Asset $candidate): bool => $candidate->id !== $storedAsset->id)
                ?->update(['primary_image' => true]);
        });
    }
}
