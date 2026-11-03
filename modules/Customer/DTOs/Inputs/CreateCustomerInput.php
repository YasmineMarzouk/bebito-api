<?php

namespace Modules\Customer\DTOs\Inputs;

final readonly class CreateCustomerInput
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public ?string $phone,
        public string $preferredLocaleCode,
        public bool $marketingOptIn,
        public ?string $manualBadge,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            trim((string) $data['name']),
            strtolower(trim((string) $data['email'])),
            (string) $data['password'],
            $data['phone'] ?? null,
            (string) ($data['preferred_locale_code'] ?? 'fr'),
            (bool) ($data['marketing_opt_in'] ?? false),
            $data['manual_badge'] ?? null,
        );
    }
}
