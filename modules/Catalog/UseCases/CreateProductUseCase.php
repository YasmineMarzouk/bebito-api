<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\ProductRepositoryInterface;
use Modules\Catalog\Contracts\Services\CatalogTranslationServiceInterface;
use Modules\Catalog\DTOs\Inputs\CatalogTranslationInput;
use Modules\Catalog\DTOs\Inputs\CreateProductInput;
use Modules\Catalog\DTOs\Inputs\ProductTranslationInput;
use Modules\Catalog\DTOs\Outputs\ProductOutput;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;

final readonly class CreateProductUseCase
{
    public function __construct(
        private ProductRepositoryInterface $products,
        private CatalogTranslationServiceInterface $translations,
        private FileStorageServiceInterface $files,
        private TransactionManagerInterface $transactions,
    ) {}

    public function execute(CreateProductInput $input): ProductOutput
    {
        $translations = $this->completeTranslations($input->translations);

        return $this->transactions->run(function () use ($input, $translations): ProductOutput {
            $product = $this->products->create($input, $translations);

            foreach ($input->images as $index => $image) {
                $this->files->store($image, new StoreAssetInput('product', $product->id, 'products', 'public', $index, $index === ($input->primaryImageIndex ?? 0)));
            }

            return ProductOutput::fromModel($this->products->find($product->id) ?? $product);
        });
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
            new ProductTranslationInput(
                localeCode: 'en',
                name: $translated[1]->name,
                slug: $translated[1]->slug,
                shortDescription: $english?->shortDescription ?? $translatedShortDescription,
                description: $translated[1]->description,
            ),
        ];
    }
}
