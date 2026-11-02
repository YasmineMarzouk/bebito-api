<?php

namespace Modules\Catalog\UseCases;

use Illuminate\Http\UploadedFile;
use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\Contracts\Services\CatalogTranslationServiceInterface;
use Modules\Catalog\DTOs\Inputs\CreateCategoryInput;
use Modules\Catalog\DTOs\Outputs\CategoryOutput;
use Modules\Catalog\Exceptions\CategoryNameAlreadyExistsException;
use Modules\Catalog\Exceptions\CategorySlugAlreadyExistsException;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;

final readonly class CreateCategoryUseCase
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
        private CatalogTranslationServiceInterface $translations,
        private FileStorageServiceInterface $files,
        private TransactionManagerInterface $transactions,
    ) {}

    public function execute(CreateCategoryInput $input, ?UploadedFile $banner = null): CategoryOutput
    {
        $input = new CreateCategoryInput(
            $input->isActive,
            $this->translations->completeFrenchTranslation($input->translations[0], $input->translations[1] ?? null)
        );

        if ($this->categories->frenchNameExists($input->translations[0]->name)) {
            throw new CategoryNameAlreadyExistsException;
        }

        foreach ($input->translations as $translation) {
            if ($translation->slug !== null && $this->categories->slugExists($translation->localeCode, $translation->slug)) {
                throw new CategorySlugAlreadyExistsException;
            }
        }

        return $this->transactions->run(function () use ($input, $banner): CategoryOutput {
            $category = $this->categories->create($input);

            if ($banner !== null) {
                $this->files->store($banner, new StoreAssetInput('category', $category->id, 'categories', 'public', 0, true));
            }

            return CategoryOutput::fromModel($this->categories->find($category->id) ?? $category);
        });
    }
}
