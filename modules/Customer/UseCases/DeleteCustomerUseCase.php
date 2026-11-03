<?php

namespace Modules\Customer\UseCases;

use Modules\Customer\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\Customer\Exceptions\CustomerNotFoundException;

final class DeleteCustomerUseCase
{
    public function __construct(private readonly CustomerRepositoryInterface $customers) {}

    public function execute(int $customerId): void
    {
        $customer = $this->customers->find($customerId);

        if ($customer === null) {
            throw new CustomerNotFoundException;
        }

        $this->customers->delete($customer);
    }
}
