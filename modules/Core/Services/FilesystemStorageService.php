<?php

namespace Modules\Core\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Contracts\Services\FilesystemStorageServiceInterface;
use RuntimeException;

final class FilesystemStorageService implements FilesystemStorageServiceInterface
{
    public function store(UploadedFile $file, string $directory, string $disk): string
    {
        $path = Storage::disk($disk)->putFile(trim($directory, '/'), $file);

        if ($path === false) {
            throw new RuntimeException('The uploaded file could not be stored.');
        }

        return $path;
    }

    public function delete(string $disk, string $path): void
    {
        Storage::disk($disk)->delete($path);
    }

    public function url(string $disk, string $path): string
    {
        return Storage::disk($disk)->url($path);
    }
}
