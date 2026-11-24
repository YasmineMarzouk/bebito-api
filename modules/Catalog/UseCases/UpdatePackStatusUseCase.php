<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Contracts\Services\PackAvailabilityServiceInterface;
use Modules\Catalog\Contracts\Services\PackCompositionValidationServiceInterface;
use Modules\Catalog\DTOs\Inputs\PackComponentInput;
use Modules\Catalog\DTOs\Inputs\PackVariantInput;
use Modules\Catalog\DTOs\Inputs\PackVariantMappingInput;
use Modules\Catalog\DTOs\Outputs\PackOutput;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Catalog\Exceptions\PackNotFoundException;
use Modules\Catalog\Models\Pack;
use Modules\Core\Contracts\Services\TransactionManagerInterface;

final readonly class UpdatePackStatusUseCase
{
    public function __construct(
        private PackRepositoryInterface $packs,
        private PackAvailabilityServiceInterface $availability,
        private PackCompositionValidationServiceInterface $composition,
        private TransactionManagerInterface $transactions,
    ) {}

    public function execute(int $packId, int $status): PackOutput
    {
        return $this->transactions->run(function () use ($packId, $status): PackOutput {
            $pack = $this->packs->findForUpdate($packId);
            if ($pack === null) {
                throw new PackNotFoundException;
            }

            $publishing = $status === ProductStatusEnum::Published->value;
            if ($publishing) {
                $this->composition->validate($this->components($pack), $this->variants($pack), true);
            }

            $wasNeverPublished = $pack->first_published_at === null;
            $pack = $this->packs->update($pack, ['status' => $status]);
            if ($publishing && $wasNeverPublished) {
                $pack = $this->packs->markFirstPublished($pack);
            }

            $savedPack = $this->packs->find($pack->id) ?? $pack;

            return PackOutput::fromModel($savedPack, $this->availability->for($savedPack));
        }, attempts: 3);
    }

    /** @return array<int, PackComponentInput> */
    private function components(Pack $pack): array
    {
        return $pack->components
            ->map(fn ($component): PackComponentInput => new PackComponentInput($component->product_id, $component->quantity))
            ->all();
    }

    /** @return array<int, PackVariantInput> */
    private function variants(Pack $pack): array
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
}
