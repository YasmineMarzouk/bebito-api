<?php

namespace Modules\Customer\DTOs\Outputs;

use App\Models\User;
use Modules\Customer\Models\CustomerAddress;
use Modules\Customer\Models\CustomerChild;
use Modules\Customer\Models\CustomerProfile;

final readonly class CustomerProfileOutput
{
    /**
     * @param  array<int, array{name: string, birthDate: ?string, gender: string, newborn: bool}>  $children
     */
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public ?string $gender,
        public ?string $address,
        public ?string $city,
        public ?string $postalCode,
        public array $children,
    ) {}

    /** @param iterable<CustomerChild> $children */
    public static function fromModel(User $customer, ?CustomerProfile $profile, ?CustomerAddress $address, iterable $children): self
    {
        return new self(
            $customer->name,
            $customer->email,
            $profile?->phone,
            $customer->gender,
            $address?->line_one,
            $address?->city,
            $address?->postal_code,
            collect($children)->map(fn (CustomerChild $child): array => [
                'name' => $child->name,
                'birthDate' => $child->birth_date?->toDateString(),
                'gender' => $child->gender,
                'newborn' => $child->newborn,
            ])->values()->all(),
        );
    }
}
