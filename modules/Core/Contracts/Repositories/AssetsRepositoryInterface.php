<?php

namespace Modules\Core\Contracts\Repositories;

use Modules\Core\Models\Asset;

interface AssetsRepositoryInterface
{
    /** @param array{resource_type: string, resource_id: int, disk: string, path: string, original_name: string, mime_type: ?string, size: ?int, sort_order: int, is_primary: bool} $attributes */
    public function create(array $attributes): Asset;

    public function delete(Asset $asset): void;
}
