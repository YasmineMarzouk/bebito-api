<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Contracts\Services\PackAvailabilityServiceInterface;
use Modules\Catalog\DTOs\Outputs\PackOutput;
use Modules\Catalog\Exceptions\PackNotFoundException;

final readonly class GetShopPackUseCase
{
    public function __construct(private PackRepositoryInterface $packs, private PackAvailabilityServiceInterface $availability) {}

    public function execute(string $identifier): PackOutput
    {
        $pack = $this->packs->findShop($identifier);
        if ($pack === null) {
            throw new PackNotFoundException;
        }

        return PackOutput::fromModel($pack, $this->availability->for($pack));
    }
}
