<?php

namespace Modules\Customer\Contracts\Services;

use App\Models\User;
use Modules\Customer\Models\CustomerProfile;
use Modules\Orders\Contracts\ValueObjects\CustomerOrderStatistics;

interface CustomerBadgeResolverInterface
{
    public function resolve(User $customer, ?CustomerProfile $profile, CustomerOrderStatistics $statistics): ?string;
}
