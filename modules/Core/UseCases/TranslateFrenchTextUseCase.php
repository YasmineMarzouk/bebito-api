<?php

namespace Modules\Core\UseCases;

use Modules\Core\Contracts\Services\TextTranslationServiceInterface;
use Modules\Core\DTOs\Outputs\TranslatedTextOutput;

final class TranslateFrenchTextUseCase
{
    public function __construct(private readonly TextTranslationServiceInterface $translator) {}

    /** @param array{title: string, slug?: ?string, description?: ?string} $payload */
    public function execute(array $payload): TranslatedTextOutput
    {
        return $this->translator->translateFrenchToEnglish(
            $payload['title'],
            $payload['slug'] ?? null,
            $payload['description'] ?? null,
        );
    }
}
