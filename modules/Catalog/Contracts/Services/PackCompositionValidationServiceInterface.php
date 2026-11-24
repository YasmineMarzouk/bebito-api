<?php

namespace Modules\Catalog\Contracts\Services;

use Modules\Catalog\DTOs\Inputs\PackComponentInput;
use Modules\Catalog\DTOs\Inputs\PackVariantInput;

interface PackCompositionValidationServiceInterface
{
    /** @param array<int, PackComponentInput> $components @param array<int, PackVariantInput> $variants */
    public function validate(array $components, array $variants, bool $publishing): void;

    /**
     * @param  array<int, PackComponentInput>  $components
     * @return array<int, PackVariantInput>
     */
    public function deriveVariants(array $components): array;

    /** @param array<int, PackComponentInput> $components */
    public function compareAtPriceAmount(array $components): int;
}
