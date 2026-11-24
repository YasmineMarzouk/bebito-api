<?php

namespace Modules\Catalog\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Bestseller;

final class DeleteBestsellerUseCase
{
    public function execute(int $position): void
    {
        DB::transaction(function () use ($position): void {
            Bestseller::query()
                ->where('position', $position)
                ->lockForUpdate()
                ->firstOrFail()
                ->forceDelete();
        }, attempts: 3);
    }
}
