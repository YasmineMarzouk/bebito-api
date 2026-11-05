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
        public string $shoppingFor,
        public ?string $gender,
        /** @var list<array{name: string, birthDate: string, gender: string}> */
        public array $children,
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
            (string) ($data['shopping_for'] ?? 'gift'),
            $data['gender'] ?? null,
            array_map(
                fn (array $child): array => [
                    'name' => trim((string) $child['name']),
                    'birthDate' => (string) $child['birth_date'],
                    'gender' => (string) $child['gender'],
                ],
                $data['children'] ?? [],
            ),
        );
    }
}
