<?php

namespace Modules\Catalog\Repositories;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\CollectionRepositoryInterface;
use Modules\Catalog\DTOs\Inputs\CatalogTranslationInput;
use Modules\Catalog\DTOs\Inputs\CreateCollectionInput;
use Modules\Catalog\DTOs\Inputs\UpdateCollectionInput;
use Modules\Catalog\Models\Collection;
use Modules\Catalog\Models\CollectionTranslation;

final class CollectionRepository implements CollectionRepositoryInterface
{
    public function all(): EloquentCollection
    {
        return Collection::query()->with(['translations', 'primaryAsset'])->withCount('products')->orderBy('sort_order')->orderBy('id')->get();
    }

    public function find(int $id): ?Collection
    {
        return Collection::query()->with(['translations', 'primaryAsset'])->withCount('products')->find($id);
    }

    public function create(CreateCollectionInput $input): Collection
    {
        return DB::transaction(function () use ($input): Collection {
            $fallback = $this->fallbackTranslation($input->translations);
            $collection = Collection::query()->create([
                'name' => $fallback->name,
                'slug' => $fallback->slug,
                'description' => $fallback->description,
                'is_active' => $input->isActive,
                'is_featured' => $input->isFeatured,
                'sort_order' => $input->sortOrder,
            ]);
            $this->storeTranslations($collection, $input->translations);

            return $this->find($collection->id) ?? $collection;
        });
    }

    public function update(Collection $collection, UpdateCollectionInput $input): Collection
    {
        return DB::transaction(function () use ($collection, $input): Collection {
            $fallback = $this->fallbackTranslation($input->translations);
            $collection->update([
                'name' => $fallback->name,
                'slug' => $fallback->slug,
                'description' => $fallback->description,
                'is_active' => $input->isActive,
                'is_featured' => $input->isFeatured,
                'sort_order' => $input->sortOrder,
            ]);
            $this->storeTranslations($collection, $input->translations);

            return $this->find($collection->id) ?? $collection;
        });
    }

    public function delete(Collection $collection): void
    {
        $collection->delete();
    }

    public function slugExists(string $localeCode, string $slug, ?int $excludingCollectionId = null): bool
    {
        return CollectionTranslation::query()
            ->where('locale_code', $localeCode)
            ->where('slug', $slug)
            ->when($excludingCollectionId !== null, fn ($query) => $query->where('collection_id', '!=', $excludingCollectionId))
            ->exists();
    }

    /** @param array<int, CatalogTranslationInput> $translations */
    private function storeTranslations(Collection $collection, array $translations): void
    {
        foreach ($translations as $translation) {
            $collection->translations()->updateOrCreate(
                ['locale_code' => $translation->localeCode],
                [
                    'name' => $translation->name,
                    'slug' => $translation->slug,
                    'description' => $translation->description,
                ],
            );
        }
    }

    /** @param array<int, CatalogTranslationInput> $translations */
    private function fallbackTranslation(array $translations): CatalogTranslationInput
    {
        foreach ($translations as $translation) {
            if ($translation->localeCode === 'fr') {
                return $translation;
            }
        }

        return $translations[0];
    }
}
