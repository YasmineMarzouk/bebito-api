<?php

namespace Modules\Core\Services;

use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Modules\Core\Contracts\Repositories\AssetsRepositoryInterface;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\Contracts\Services\FilesystemStorageServiceInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;
use Modules\Core\Models\Asset;

final class FileStorageService implements FileStorageServiceInterface
{
    public function __construct(
        private readonly FilesystemStorageServiceInterface $filesystem,
        private readonly AssetsRepositoryInterface $assets,
    ) {}

    public function store(UploadedFile $file, StoreAssetInput $input): Asset
    {
        $this->assertValidInput($input);

        $path = $this->filesystem->store($file, $input->directory, $input->disk);

        return $this->assets->create([
            'resource_type' => $input->resourceType,
            'resource_id' => $input->resourceId,
            'disk' => $input->disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'sort_order' => $input->sortOrder,
            'is_primary' => $input->isPrimary,
        ]);
    }

    public function delete(Asset $asset): void
    {
        $this->filesystem->delete($asset->disk, $asset->path);
        $this->assets->delete($asset);
    }

    public function url(Asset $asset): string
    {
        return $this->filesystem->url($asset->disk, $asset->path);
    }

    private function assertValidInput(StoreAssetInput $input): void
    {
        if ($input->resourceType === '' || $input->resourceId < 1 || trim($input->directory, '/') === '') {
            throw new InvalidArgumentException('The asset storage input is invalid.');
        }
    }
}
