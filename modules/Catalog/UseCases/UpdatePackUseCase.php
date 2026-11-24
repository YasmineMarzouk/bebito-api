<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Contracts\Services\PackAvailabilityServiceInterface;
use Modules\Catalog\Contracts\Services\PackCompositionValidationServiceInterface;
use Modules\Catalog\DTOs\Inputs\PackComponentInput;
use Modules\Catalog\DTOs\Inputs\PackVariantInput;
use Modules\Catalog\DTOs\Inputs\PackVariantMappingInput;
use Modules\Catalog\DTOs\Inputs\UpdatePackInput;
use Modules\Catalog\DTOs\Outputs\PackOutput;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Exceptions\PackCompositionLockedException;
use Modules\Catalog\Exceptions\PackNotFoundException;
use Modules\Catalog\Models\Pack;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;

final readonly class UpdatePackUseCase
{
    public function __construct(
        private PackRepositoryInterface $packs,
        private PackAvailabilityServiceInterface $availability,
        private PackCompositionValidationServiceInterface $composition,
        private TransactionManagerInterface $transactions,
        private FileStorageServiceInterface $files,
    ) {}

    public function execute(int $packId, UpdatePackInput $input): PackOutput
    {
        return $this->transactions->run(function () use ($packId, $input): PackOutput {
            $pack = $this->packs->findForUpdate($packId);
            if ($pack === null) {
                throw new PackNotFoundException;
            }

            if ($input->components !== null && $pack->first_published_at !== null) {
                throw new PackCompositionLockedException;
            }

            $components = $input->components ?? $this->currentComponents($pack);
            $variants = $input->components === null
                ? $this->currentVariants($pack)
                : $this->composition->deriveVariants($components);
            $publishing = $input->status === ProductStatusEnum::Published->value;
            $this->composition->validate($components, $variants, $publishing);
            $compareAtPriceAmount = $input->components === null
                ? $pack->compare_at_price_amount ?? $this->composition->compareAtPriceAmount($components)
                : $this->composition->compareAtPriceAmount($components);

            $attributes = [
                'status' => $input->status,
                'gender' => $input->gender,
                'price_amount' => $input->priceAmount,
                'compare_at_price_amount' => $compareAtPriceAmount,
                'discount_percentage' => $this->discountPercentage($input->priceAmount, $compareAtPriceAmount),
                'low_stock_threshold' => $input->lowStockThreshold,
            ];

            $pack = $this->packs->update($pack, $attributes);
            $this->packs->syncTranslations($pack, $input->translations);
            $this->packs->syncOrganization($pack, $input->categoryIds, $input->collectionIds, $input->tags);

            if ($input->components !== null) {
                $this->packs->replaceComposition($pack, $components, $variants);
            }

            foreach ($input->images as $index => $image) {
                $this->files->store($image, new StoreAssetInput('pack', $pack->id, 'packs', 'public', $pack->assets->count() + $index));
            }

            if ($publishing && $pack->first_published_at === null) {
                $this->packs->markFirstPublished($pack);
            }

            $savedPack = $this->packs->find($pack->id) ?? $pack;

            return PackOutput::fromModel($savedPack, $this->availability->for($savedPack));
        }, attempts: 3);
    }

    /** @return array<int, PackComponentInput> */
    private function currentComponents(Pack $pack): array
    {
        return $pack->components
            ->map(fn ($component): PackComponentInput => new PackComponentInput($component->product_id, $component->quantity))
            ->all();
    }

    /** @return array<int, PackVariantInput> */
    private function currentVariants(Pack $pack): array
    {
        return $pack->allVariants
            ->filter(fn ($variant): bool => $variant->is_active)
            ->map(fn ($variant): PackVariantInput => new PackVariantInput(
                id: $variant->id,
                attributes: $variant->attributes ?? [],
                isDefault: $variant->is_default,
                isActive: $variant->is_active,
                componentMappings: $variant->componentVariantMappings
                    ->map(fn ($mapping): PackVariantMappingInput => new PackVariantMappingInput($mapping->packComponent->product_id, $mapping->product_variant_id))
                    ->all(),
            ))
            ->all();
    }

    private function discountPercentage(int $priceAmount, int $compareAtPriceAmount): ?float
    {
        return $compareAtPriceAmount > $priceAmount
            ? round((($compareAtPriceAmount - $priceAmount) / $compareAtPriceAmount) * 100, 2)
            : null;
    }
}
