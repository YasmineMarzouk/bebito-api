<?php

namespace Modules\Customer\Contracts\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Customer\DTOs\Inputs\CreateCustomerInput;
use Modules\Customer\DTOs\Inputs\UpdateCustomerInput;
use Modules\Customer\Models\CustomerProfile;

interface CustomerRepositoryInterface
{
    /** @return Collection<int, User> */
    public function all(): Collection;

    public function find(int $id): ?User;

    public function profileFor(int $userId): ?CustomerProfile;

    public function create(CreateCustomerInput $input): User;

    public function update(User $customer, UpdateCustomerInput $input): User;

    public function setBlocked(User $customer, bool $isBlocked): User;

    public function delete(User $customer): void;

    public function emailExists(string $email, ?int $excludingCustomerId = null): bool;
}
