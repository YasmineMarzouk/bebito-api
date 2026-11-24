<?php

namespace Modules\Customer\DTOs\Inputs;

final readonly class UpdateCustomerProfileInput
{
    /**
     * @param  list<array{name: string, birthDate: ?string, gender: string, newborn: bool}>|null  $children
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $phone,
        public string $address,
        public string $city,
        public string $postalCode,
        public ?array $children,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            trim((string) $data['name']),
            trim((string) $data['email']),
            trim((string) $data['phone']),
            trim((string) $data['address']),
            trim((string) $data['city']),
            trim((string) $data['postal_code']),
            array_key_exists('children', $data)
                ? array_map(
                    fn (array $child): array => [
                        'name' => trim((string) $child['name']),
                        'birthDate' => $child['birth_date'] ?? null,
                        'gender' => (string) $child['gender'],
                        'newborn' => (bool) ($child['newborn'] ?? false),
                    ],
                    $data['children'],
                )
                : null,
        );
    }
}
