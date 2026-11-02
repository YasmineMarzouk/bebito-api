<?php

namespace Modules\Catalog\UseCases;

use Illuminate\Http\UploadedFile;
use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\Exceptions\CategoryNotFoundException;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;

final class UploadCategoryBannerUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categories, private readonly FileStorageServiceInterface $files) {}

    public function execute(int $categoryId, UploadedFile $file): string
    {
        if ($this->categories->find($categoryId) === null) {
            throw new CategoryNotFoundException;
        }
        $asset = $this->files->store($file, new StoreAssetInput('category', $categoryId, 'categories', 'public', 0, true));

        return $this->files->url($asset);
    }
}
