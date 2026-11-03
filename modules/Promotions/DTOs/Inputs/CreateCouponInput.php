<?php

namespace Modules\Promotions\DTOs\Inputs;

final readonly class CreateCouponInput
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
        return new self(
            code: strtoupper(trim((string) $data['code'])),
            type: (string) $data['type'],
            percentage: array_key_exists('percentage', $data) && $data['percentage'] !== null ? (int) $data['percentage'] : null,
            fixedAmount: array_key_exists('fixed_amount', $data) && $data['fixed_amount'] !== null ? (int) $data['fixed_amount'] : null,
            minOrderAmount: (int) ($data['min_order_amount'] ?? 0),
            usageLimit: array_key_exists('usage_limit', $data) && $data['usage_limit'] !== null ? (int) $data['usage_limit'] : null,
            expiresAt: array_key_exists('expires_at', $data) ? $data['expires_at'] : null,
            isActive: (bool) ($data['is_active'] ?? true),
        );
    }
}
