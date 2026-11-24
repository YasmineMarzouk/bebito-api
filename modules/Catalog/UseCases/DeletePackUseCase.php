<?php

namespace Modules\Catalog\UseCases;

use Modules\Catalog\Contracts\Repositories\PackRepositoryInterface;
use Modules\Catalog\Exceptions\PackNotFoundException;

final readonly class DeletePackUseCase
{
    public function __construct(private PackRepositoryInterface $packs) {}

    public function execute(int $packId): void
    {
        $pack = $this->packs->find($packId);
        if ($pack === null) {
            throw new PackNotFoundException;
        }

        $this->packs->delete($pack);
    }
}
