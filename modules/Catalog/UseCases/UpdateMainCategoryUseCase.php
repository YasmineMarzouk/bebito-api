<?php

namespace Modules\Catalog\UseCases;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\DTOs\Outputs\MainCategoryOutput;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\MainCategory;

final class UpdateMainCategoryUseCase
{
    public function execute(int $categoryId, int $position): MainCategoryOutput
    {
        $mainCategoryId = DB::transaction(function () use ($categoryId, $position): int {
            $existingMainCategory = MainCategory::query()
                ->where('position', $position)
                ->lockForUpdate()
                ->first();

            if ($existingMainCategory !== null) {
                $existingMainCategory->delete();
            }

            return MainCategory::query()->create([
                'category_id' => $categoryId,
                'position' => $position,
            ])->id;
        }, attempts: 3);

        $mainCategory = MainCategory::query()
            ->with(['category' => Category::categoryDetails()])
            ->findOrFail($mainCategoryId);

        return MainCategoryOutput::fromModel($mainCategory);
    }
}
