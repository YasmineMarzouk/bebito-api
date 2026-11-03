<?php

namespace Modules\Customer\DTOs\Inputs;

final readonly class BlockCustomerInput
{
    public function __construct(public bool $isBlocked) {}

    /** @param array{is_blocked: bool} $data */
    public static function from(array $data): self
    {
        return new self((bool) $data['is_blocked']);
    }
}
