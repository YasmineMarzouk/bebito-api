<?php

namespace Modules\Core\Contracts\Services;

use Illuminate\Http\UploadedFile;
use Modules\Core\DTOs\Inputs\StoreAssetInput;
use Modules\Core\Models\Asset;

interface FileStorageServiceInterface
{
    public function store(UploadedFile $file, StoreAssetInput $input): Asset;

    public function delete(Asset $asset): void;

    public function url(Asset $asset): string;
}
