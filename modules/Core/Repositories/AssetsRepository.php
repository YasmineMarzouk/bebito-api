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
            if ($attributes['is_primary']) {
                Asset::query()
                    ->where('resource_type', $attributes['resource_type'])
                    ->where('resource_id', $attributes['resource_id'])
                    ->update(['is_primary' => false]);
            }

            return Asset::query()->create($attributes);
        });
    }

    public function delete(Asset $asset): void
    {
        $asset->delete();
    }
}
