<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\Contracts\Services\CatalogTranslationServiceInterface;
use Modules\Catalog\DTOs\Inputs\UpdateCategoryInput;
use Modules\Catalog\DTOs\Outputs\CategoryOutput;
use Modules\Catalog\Exceptions\CategoryNotFoundException;
use Modules\Catalog\Exceptions\CategorySlugAlreadyExistsException;

final class UpdateCategoryUseCase
{
    public function __construct(private readonly CategoryRepositoryInterface $categories, private readonly CatalogTranslationServiceInterface $translations) {}

    public function execute(int $categoryId, UpdateCategoryInput $input): CategoryOutput
    {
        $category = $this->categories->find($categoryId);

        if ($category === null) {
            throw new CategoryNotFoundException;
        }

        $input = new UpdateCategoryInput($input->isActive, $this->translations->completeFrenchTranslation($input->translations[0], $input->translations[1] ?? null));

        foreach ($input->translations as $translation) {
            if ($translation->slug !== null && $this->categories->slugExists($translation->localeCode, $translation->slug, $category->id)) {
                throw new CategorySlugAlreadyExistsException;
            }
        }

        return CategoryOutput::fromModel($this->categories->update($category, $input));
    }
}
