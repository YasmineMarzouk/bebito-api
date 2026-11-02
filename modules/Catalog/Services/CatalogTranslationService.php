<?php

namespace Modules\Catalog\Services;

use Modules\Catalog\Contracts\Services\CatalogTranslationServiceInterface;
use Modules\Catalog\DTOs\Inputs\CatalogTranslationInput;
use Modules\Core\Contracts\Services\TextTranslationServiceInterface;

final class CatalogTranslationService implements CatalogTranslationServiceInterface
{
    public function __construct(private readonly TextTranslationServiceInterface $translator) {}

    public function completeFrenchTranslation(CatalogTranslationInput $french, ?CatalogTranslationInput $english = null): array
    {
        $translated = $this->translator->translateFrenchToEnglish($french->name, $french->slug, $french->description);

        return [
            $french,
            new CatalogTranslationInput('en', $english?->name ?: $translated->title, $french->slug === null ? null : ($english?->slug ?: $translated->slug), $french->description === null ? null : ($english?->description ?: $translated->description)),
        ];
    }
}
