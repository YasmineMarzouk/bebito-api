<?php

namespace Modules\Customer\UseCases;

use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Customer\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Contracts\Services\CustomerBadgeResolverInterface;
use Modules\Customer\DTOs\Inputs\CreateCustomerInput;
use Modules\Customer\DTOs\Outputs\CustomerOutput;
use Modules\Customer\Exceptions\CustomerEmailAlreadyExistsException;
use Modules\Orders\Contracts\Services\CustomerOrderStatisticsServiceInterface;

final class CreateCustomerUseCase
{
    public function __construct(private readonly CustomerRepositoryInterface $customers, private readonly CustomerOrderStatisticsServiceInterface $orders, private readonly CustomerBadgeResolverInterface $badges, private readonly TransactionManagerInterface $transactions) {}

    public function execute(CreateCustomerInput $input): CustomerOutput
    {
        if ($this->customers->emailExists($input->email)) {
            throw new CustomerEmailAlreadyExistsException;
        }

        return $this->transactions->run(function () use ($input): CustomerOutput {
            $customer = $this->customers->create($input);
            $profile = $this->customers->profileFor($customer->id);
            $statistics = $this->orders->forCustomer($customer->id);

            return CustomerOutput::fromModel($customer, $profile, $statistics, $this->badges->resolve($customer, $profile, $statistics));
        });
    }
}
