<?php

namespace Modules\Customer\DTOs\Outputs;

use App\Models\User;
use Modules\Customer\Models\CustomerProfile;
use Modules\Orders\Contracts\ValueObjects\CustomerOrderStatistics;

final readonly class CustomerOutput
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $phone,
        public string $preferredLocaleCode,
        public bool $marketingOptIn,
        public bool $isBlocked,
        public ?string $badge,
        public int $orderCount,
        public int $totalSpentAmount,
        public string $createdAt,
    ) {}

    public static function fromModel(User $user, ?CustomerProfile $profile, CustomerOrderStatistics $statistics, ?string $badge): self
    {
        return new self(
            $user->id,
            $user->name,
            $user->email,
            $profile?->phone,
            $profile?->preferred_locale_code ?? 'fr',
            $profile?->marketing_opt_in ?? false,
            $user->is_blocked,
            $badge,
            $statistics->orderCount,
            $statistics->totalSpentAmount,
            $user->created_at->toDateString(),
        );
    }
}
