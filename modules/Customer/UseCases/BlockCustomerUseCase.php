<?php

namespace Modules\Customer\UseCases;

use Modules\Customer\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Contracts\Services\CustomerBadgeResolverInterface;
use Modules\Customer\DTOs\Inputs\BlockCustomerInput;
use Modules\Customer\DTOs\Outputs\CustomerOutput;
use Modules\Customer\Exceptions\CustomerNotFoundException;
use Modules\Orders\Contracts\Services\CustomerOrderStatisticsServiceInterface;

final class BlockCustomerUseCase
{
    public function __construct(private readonly CustomerRepositoryInterface $customers, private readonly CustomerOrderStatisticsServiceInterface $orders, private readonly CustomerBadgeResolverInterface $badges) {}

    public function execute(int $customerId, BlockCustomerInput $input): CustomerOutput
    {
        $customer = $this->customers->find($customerId);

        if ($customer === null) {
            throw new CustomerNotFoundException;
        }

        $customer = $this->customers->setBlocked($customer, $input->isBlocked);
        $profile = $this->customers->profileFor($customer->id);
        $statistics = $this->orders->forCustomer($customer->id);

        return CustomerOutput::fromModel($customer, $profile, $statistics, $this->badges->resolve($customer, $profile, $statistics));
    }
}
