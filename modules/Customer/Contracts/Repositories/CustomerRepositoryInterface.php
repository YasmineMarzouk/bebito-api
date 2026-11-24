<?php

namespace Modules\Customer\Contracts\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;
use Modules\Customer\DTOs\Inputs\CreateCustomerInput;
use Modules\Customer\DTOs\Inputs\UpdateCustomerInput;
use Modules\Customer\DTOs\Inputs\UpdateCustomerProfileInput;
use Modules\Customer\Models\CustomerAddress;
use Modules\Customer\Models\CustomerChild;
use Modules\Customer\Models\CustomerProfile;

interface CustomerRepositoryInterface
{
    /** @return Collection<int, User> */
    public function all(): Collection;

    /** @return LengthAwarePaginator<int, User> */
    public function paginate(int $perPage, int $page): LengthAwarePaginator;

    public function find(int $id): ?User;

    public function profileFor(int $userId): ?CustomerProfile;

    public function addressFor(int $userId): ?CustomerAddress;

    /** @return Collection<int, CustomerChild> */
    public function childrenFor(int $userId): Collection;

    public function create(CreateCustomerInput $input): User;

    /** @return LazyCollection<int, User> */
    public function marketingOptedIn(): LazyCollection;

    public function update(User $customer, UpdateCustomerInput $input): User;

    public function updateProfile(User $customer, UpdateCustomerProfileInput $input): User;

    public function setBlocked(User $customer, bool $isBlocked): User;

    public function delete(User $customer): void;

    public function emailExists(string $email, ?int $excludingCustomerId = null): bool;
}
