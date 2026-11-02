<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\Contracts\Services\CatalogTranslationServiceInterface;
use Modules\Catalog\DTOs\Inputs\CatalogTranslationInput;
use Modules\Catalog\DTOs\Inputs\ProductTranslationInput;
use Modules\Catalog\DTOs\Inputs\UpdateProductInput;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Catalog\Exceptions\ProductImageNotFoundException;
use Modules\Catalog\Exceptions\ProductNotFoundException;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;

final readonly class UpdateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private CatalogTranslationServiceInterface $translations,
        private FileStorageServiceInterface $files,
    ) {}

    public function execute(int $id, UpdateProductInput $input): ProductOutput
    {
        $existing = $this->products->find($id);

        if ($existing === null) {
            throw new ProductNotFoundException;
        }

        if ($input->primaryImageId !== null && $this->products->findAsset($id, $input->primaryImageId) === null) {
            throw new ProductImageNotFoundException;
        }

        $product = $this->products->update($existing, $input, $this->completeTranslations($input->translations));
        $nextSortOrder = $product->assets->count();

        foreach ($input->images as $index => $image) {
            $isPrimary = $input->primaryImageIndex === null
                ? $product->assets->isEmpty() && $index === 0
                : $index === $input->primaryImageIndex;
            $this->files->store($image, new StoreAssetInput('product', $product->id, 'products', 'public', $nextSortOrder + $index, $isPrimary));
        }

        if ($input->primaryImageId !== null && ! $this->products->setPrimaryAsset($product->id, $input->primaryImageId)) {
            throw new ProductImageNotFoundException;
        }

        return ProductOutput::fromModel($this->products->find($product->id) ?? $product);
    }

    /** @param array<int, ProductTranslationInput> $translations @return array<int, ProductTranslationInput> */
    private function completeTranslations(array $translations): array
    {
        $french = collect($translations)->firstWhere('localeCode', 'fr');
        $english = collect($translations)->firstWhere('localeCode', 'en');

        if ($french === null) {
            return $translations;
        }

        $translated = $this->translations->completeFrenchTranslation(
            new CatalogTranslationInput($french->localeCode, $french->name, $french->slug, $french->description),
            $english === null ? null : new CatalogTranslationInput($english->localeCode, $english->name, $english->slug, $english->description),
        );

        $translatedShortDescription = $french->shortDescription === null ? null : $this->translations->completeFrenchTranslation(
            new CatalogTranslationInput('fr', $french->name, null, $french->shortDescription),
            null,
        )[1]->description;

        return [
            $french,
            new ProductTranslationInput('en', $translated[1]->name, $translated[1]->slug, $english?->shortDescription ?? $translatedShortDescription, $translated[1]->description),
        ];
    }
}
