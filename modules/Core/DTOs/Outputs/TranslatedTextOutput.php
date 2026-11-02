<?php

namespace Modules\Core\DTOs\Outputs;

final readonly class TranslatedTextOutput
{
    public function __construct(public string $title, public ?string $slug, public ?string $description) {}
}
