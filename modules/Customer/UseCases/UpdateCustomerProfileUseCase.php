<?php

namespace Modules\Customer\UseCases;

use App\Models\User;
use Modules\Core\Contracts\Services\TransactionManagerInterface;
use Modules\Customer\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\Customer\DTOs\Inputs\UpdateCustomerProfileInput;
use Modules\Customer\DTOs\Outputs\CustomerProfileOutput;

final class UpdateCustomerProfileUseCase
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly TransactionManagerInterface $transactions,
    ) {}

    public function execute(User $customer, UpdateCustomerProfileInput $input): CustomerProfileOutput
    {
        return $this->transactions->run(function () use ($customer, $input): CustomerProfileOutput {
            $customer = $this->customers->updateProfile($customer, $input);

            return CustomerProfileOutput::fromModel(
                $customer,
                $this->customers->profileFor($customer->id),
                $this->customers->addressFor($customer->id),
                $this->customers->childrenFor($customer->id),
            );
        });
    }
}
