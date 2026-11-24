<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Contracts\Services\PackAvailabilityServiceInterface;
use Modules\Catalog\DTOs\Outputs\PackOutput;

final readonly class ListShopPacksUseCase
{
    public function __construct(private PackRepositoryInterface $packs, private PackAvailabilityServiceInterface $availability) {}

    /** @return array<int, PackOutput> */
    public function execute(): array
    {
        return $this->packs->shop()
            ->map(fn ($pack): PackOutput => PackOutput::fromModel($pack, $this->availability->for($pack)))
            ->all();
    }
}
