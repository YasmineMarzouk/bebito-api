<?php

namespace Modules\Catalog\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\MainCategory;

final class DeleteMainCategoryUseCase
{
    public function execute(int $position): void
    {
        DB::transaction(function () use ($position): void {
            MainCategory::query()
                ->where('position', $position)
                ->lockForUpdate()
                ->firstOrFail()
                ->delete();
        }, attempts: 3);
    }
}
