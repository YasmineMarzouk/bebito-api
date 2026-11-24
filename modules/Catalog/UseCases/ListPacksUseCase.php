<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Contracts\Services\PackAvailabilityServiceInterface;
use Modules\Catalog\DTOs\Outputs\PackOutput;
use Modules\Core\DTOs\Outputs\PaginatedOutput;

final readonly class ListPacksUseCase
{
    public function __construct(private PackRepositoryInterface $packs, private PackAvailabilityServiceInterface $availability) {}

    public function execute(int $perPage, int $page): PaginatedOutput
    {
        $packs = $this->packs->paginate($perPage, $page);

        return new PaginatedOutput($packs->getCollection()
            ->map(fn ($pack): PackOutput => PackOutput::fromModel($pack, $this->availability->for($pack)))
            ->all(), $packs->currentPage(), $packs->lastPage(), $packs->perPage(), $packs->total());
    }
}
