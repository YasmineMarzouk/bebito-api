<?php

namespace Modules\Core\Contracts\Services;

use Illuminate\Http\UploadedFile;

interface FilesystemStorageServiceInterface
{
    public function store(UploadedFile $file, string $directory, string $disk): string;

    public function delete(string $disk, string $path): void;

    public function url(string $disk, string $path): string;
}
