<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Contracts\Services\PackAvailabilityServiceInterface;
use Modules\Catalog\Contracts\Services\PackCompositionValidationServiceInterface;
use Modules\Catalog\DTOs\Inputs\CreatePackInput;
use Modules\Catalog\DTOs\Outputs\PackOutput;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Core\Contracts\Services\FileStorageServiceInterface;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Core\DTOs\Inputs\StoreAssetInput;

final readonly class CreatePackUseCase
{
    public function __construct(
        private PackRepositoryInterface $packs,
        private PackAvailabilityServiceInterface $availability,
        private PackCompositionValidationServiceInterface $composition,
        private TransactionManagerInterface $transactions,
        private FileStorageServiceInterface $files,
    ) {}

    public function execute(CreatePackInput $input): PackOutput
    {
        return $this->transactions->run(function () use ($input): PackOutput {
            $variants = $this->composition->deriveVariants($input->components);
            $this->composition->validate($input->components, $variants, $input->status === ProductStatusEnum::Published->value);

            $compareAtPriceAmount = $this->composition->compareAtPriceAmount($input->components);
            $pack = $this->packs->create([
                'status' => $input->status,
                'gender' => $input->gender,
                'price_amount' => $input->priceAmount,
                'compare_at_price_amount' => $compareAtPriceAmount,
                'discount_percentage' => $this->discountPercentage($input->priceAmount, $compareAtPriceAmount),
                'low_stock_threshold' => $input->lowStockThreshold,
            ]);
            $this->packs->update($pack, ['sku' => sprintf('BPK-%06d', $pack->id)]);
            $this->packs->syncTranslations($pack, $input->translations);
            $this->packs->syncOrganization($pack, $input->categoryIds, $input->collectionIds, $input->tags);
            $this->packs->replaceComposition($pack, $input->components, $variants);

            foreach ($input->images as $index => $image) {
                $this->files->store($image, new StoreAssetInput('pack', $pack->id, 'packs', 'public', $index, $index === 0));
            }

            if ($input->status === ProductStatusEnum::Published->value) {
                $this->packs->markFirstPublished($pack);
            }

            $savedPack = $this->packs->find($pack->id) ?? $pack;

            return PackOutput::fromModel($savedPack, $this->availability->for($savedPack));
        }, attempts: 3);
    }

    private function discountPercentage(int $priceAmount, int $compareAtPriceAmount): ?float
    {
        return $compareAtPriceAmount > $priceAmount
            ? round((($compareAtPriceAmount - $priceAmount) / $compareAtPriceAmount) * 100, 2)
            : null;
    }
}
