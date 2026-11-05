<?php

namespace Modules\Customer\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Role;
use Modules\Customer\Contracts\Repositories\CustomerRepositoryInterface;
use Modules\Customer\DTOs\Inputs\CreateCustomerInput;
use Modules\Customer\DTOs\Inputs\UpdateCustomerInput;
use Modules\Customer\Models\CustomerChild;
use Modules\Customer\Models\CustomerProfile;

final class CustomerRepository implements CustomerRepositoryInterface
{
    public function all(): Collection
    {
        return User::query()->where('user_type', UserTypeEnum::Customer->value)->orderByDesc('id')->get();
    }

    public function find(int $id): ?User
    {
        return User::query()->whereKey($id)->where('user_type', UserTypeEnum::Customer->value)->first();
    }

    public function profileFor(int $userId): ?CustomerProfile
    {
        return CustomerProfile::query()->where('user_id', $userId)->first();
    }

    public function create(CreateCustomerInput $input): User
    {
        $customer = User::query()->create([
            'name' => $input->name,
            'email' => $input->email,
            'password' => $input->password,
            'gender' => $input->gender,
            'user_type' => UserTypeEnum::Customer->value,
        ]);
        $customerRole = Role::query()->firstOrCreate(['name' => 'customer'], ['title' => 'Customer']);
        $customer->roles()->attach($customerRole->id);

        CustomerProfile::query()->create([
            'user_id' => $customer->id,
            'phone' => $input->phone,
            'preferred_locale_code' => $input->preferredLocaleCode,
            'marketing_opt_in' => $input->marketingOptIn,
            'manual_badge' => $input->manualBadge,
            'shopping_for' => $input->shoppingFor,
        ]);

        foreach ($input->children as $child) {
            CustomerChild::query()->create([
                'user_id' => $customer->id,
                'name' => $child['name'],
                'birth_date' => $child['birthDate'],
                'gender' => $child['gender'],
            ]);
        }

        return $customer->refresh();
    }

    public function update(User $customer, UpdateCustomerInput $input): User
    {
        $attributes = ['name' => $input->name, 'email' => $input->email];

        if ($input->password !== null) {
            $attributes['password'] = $input->password;
        }

        $customer->update($attributes);
        CustomerProfile::query()->updateOrCreate(['user_id' => $customer->id], [
            'phone' => $input->phone,
            'preferred_locale_code' => $input->preferredLocaleCode,
            'marketing_opt_in' => $input->marketingOptIn,
            'manual_badge' => $input->manualBadge,
        ]);

        return $customer->refresh();
    }

    public function setBlocked(User $customer, bool $isBlocked): User
    {
        $customer->update(['is_blocked' => $isBlocked]);

        return $customer->refresh();
    }

    public function delete(User $customer): void
    {
        $customer->delete();
    }

    public function emailExists(string $email, ?int $excludingCustomerId = null): bool
    {
        return User::query()->where('email', $email)->when($excludingCustomerId !== null, fn ($query) => $query->where('id', '!=', $excludingCustomerId))->exists();
    }
}
