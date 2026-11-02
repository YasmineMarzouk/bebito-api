<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\CatalogTranslationInput;
use Modules\Catalog\DTOs\Inputs\CreateCategoryInput;
use Modules\Catalog\DTOs\Inputs\UpdateCategoryInput;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\CategoryTranslation;

final class CategoryRepository implements CategoryRepositoryInterface
{
    public function all(): Collection
    {
        return Category::query()->with(['translations', 'primaryAsset'])->withCount('products')->orderBy('id')->get();
    }

    public function find(int $id): ?Category
    {
        return Category::query()->with(['translations', 'primaryAsset'])->withCount('products')->find($id);
    }

    public function create(CreateCategoryInput $input): Category
    {
        return DB::transaction(function () use ($input): Category {
            $category = Category::query()->create(['is_active' => $input->isActive]);
            $this->storeTranslations($category, $input->translations);

            return $this->find($category->id) ?? $category;
        });
    }

    public function update(Category $category, UpdateCategoryInput $input): Category
    {
        return DB::transaction(function () use ($category, $input): Category {
            $category->update(['is_active' => $input->isActive]);
            $this->storeTranslations($category, $input->translations);

            return $this->find($category->id) ?? $category;
        });
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function slugExists(string $localeCode, string $slug, ?int $excludingCategoryId = null): bool
    {
        return CategoryTranslation::query()
            ->where('locale_code', $localeCode)
            ->where('slug', $slug)
            ->when($excludingCategoryId !== null, fn ($query) => $query->where('category_id', '!=', $excludingCategoryId))
            ->exists();
    }

    public function frenchNameExists(string $name): bool
    {
        return CategoryTranslation::query()
            ->where('locale_code', 'fr')
            ->where('name', $name)
            ->exists();
    }

    /** @param array<int, CatalogTranslationInput> $translations */
    private function storeTranslations(Category $category, array $translations): void
    {
        foreach ($translations as $translation) {
            $category->translations()->updateOrCreate(
                ['locale_code' => $translation->localeCode],
                [
                    'name' => $translation->name,
                    'slug' => $translation->slug,
                    'description' => $translation->description,
                ],
            );
        }
    }
}
