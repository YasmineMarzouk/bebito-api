<?php

namespace Modules\Promotions\DTOs\Inputs;

final readonly class UpdateCouponInput
{
    public function __construct(
        public string $code,
        public string $type,
        public ?int $percentage,
        public ?int $fixedAmount,
        public int $minOrderAmount,
        public ?int $usageLimit,
        public ?string $expiresAt,
        public bool $isActive,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        $create = CreateCouponInput::from($data);

        return new self(
            $create->code,
            $create->type,
            $create->percentage,
            $create->fixedAmount,
            $create->minOrderAmount,
            $create->usageLimit,
            $create->expiresAt,
            $create->isActive,
        );
    }
}
