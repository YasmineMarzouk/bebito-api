<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Contracts\Services\PackAvailabilityServiceInterface;
use Modules\Catalog\DTOs\Outputs\PackOutput;
use Modules\Catalog\Exceptions\PackNotFoundException;
use Modules\Catalog\Exceptions\ProductImageNotFoundException;

final readonly class SetPackPrimaryImageUseCase
{
    public function __construct(
        private PackRepositoryInterface $packs,
        private PackAvailabilityServiceInterface $availability,
    ) {}

    public function execute(int $packId, int $assetId): PackOutput
    {
        $pack = $this->packs->find($packId);
        if ($pack === null) {
            throw new PackNotFoundException;
        }

        if ($this->packs->findAsset($packId, $assetId) === null
            || ! $this->packs->setPrimaryAsset($packId, $assetId)) {
            throw new ProductImageNotFoundException;
        }

        $savedPack = $this->packs->find($packId) ?? $pack;

        return PackOutput::fromModel($savedPack, $this->availability->for($savedPack));
    }
}
