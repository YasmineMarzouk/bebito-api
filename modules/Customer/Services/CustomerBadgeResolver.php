<?php

namespace Modules\Customer\Services;

use App\Models\User;
use Modules\Customer\Contracts\Services\CustomerBadgeResolverInterface;
use Modules\Customer\Models\CustomerProfile;
use Modules\Orders\Contracts\ValueObjects\CustomerOrderStatistics;

final class CustomerBadgeResolver implements CustomerBadgeResolverInterface
{
    public function resolve(User $customer, ?CustomerProfile $profile, CustomerOrderStatistics $statistics): ?string
    {
        if ($profile?->manual_badge === 'vib') {
            return 'vib';
        }

        if ($customer->created_at->isCurrentWeek() && $statistics->orderCount === 0) {
            return 'new';
        }

        if ($statistics->notPickedUpOrderCount > 0) {
            return $statistics->deliveredOrderCount <= $statistics->notPickedUpOrderCount ? 'high_risk' : 'risky';
        }

        return match (true) {
            $statistics->orderCount === 1 => 'first_order',
            $statistics->orderCount > 1 => 'cute',
            default => null,
        };
    }
}
