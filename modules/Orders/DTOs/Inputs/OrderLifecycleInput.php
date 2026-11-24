<?php

namespace Modules\Orders\DTOs\Inputs;

final readonly class OrderLifecycleInput
{
    public function __construct(public ?string $note) {}

    /** @param array{note?: string|null} $data */
    public static function from(array $data): self
    {
        return new self($data['note'] ?? null);
    }
}
