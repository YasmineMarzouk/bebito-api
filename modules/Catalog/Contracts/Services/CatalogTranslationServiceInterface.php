<?php

namespace Modules\Catalog\Contracts\Services;

use Modules\Catalog\DTOs\Inputs\CatalogTranslationInput;

interface CatalogTranslationServiceInterface
{
    /** @return array{0: CatalogTranslationInput, 1: CatalogTranslationInput} */
    public function completeFrenchTranslation(CatalogTranslationInput $french, ?CatalogTranslationInput $english = null): array;
}
