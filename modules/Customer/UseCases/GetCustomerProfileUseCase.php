<?php

namespace Modules\Customer\UseCases;

use App\Models\User;
use Modules\Customer\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\Customer\DTOs\Outputs\CustomerProfileOutput;

final class GetCustomerProfileUseCase
{
    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    public function execute(User $customer): CustomerProfileOutput
    {
        return CustomerProfileOutput::fromModel(
            $customer,
            $this->customers->profileFor($customer->id),
            $this->customers->addressFor($customer->id),
            $this->customers->childrenFor($customer->id),
        );
    }
}
