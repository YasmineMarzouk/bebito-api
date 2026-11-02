<?php

namespace Modules\Core\Contracts\Services;

use Modules\Core\DTOs\Outputs\TranslatedTextOutput;

interface TextTranslationServiceInterface
{
    public function translateFrenchToEnglish(string $title, ?string $slug, ?string $description): TranslatedTextOutput;
}
