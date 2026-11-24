<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Customer\Models\CustomerAddress;
use Modules\Customer\Models\CustomerChild;
use Modules\Customer\Models\CustomerProfile;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_and_update_only_their_own_profile(): void
    {
        $customer = User::factory()->create([
            'name' => 'Ada Customer',
            'user_type' => UserTypeEnum::Customer->value,
            'gender' => 'mamito',
        ]);
        $otherCustomer = User::factory()->create([
            'name' => 'Other Customer',
            'user_type' => UserTypeEnum::Customer->value,
            'gender' => 'papito',
        ]);
        CustomerProfile::query()->create(['user_id' => $customer->id, 'phone' => '+21620000000']);
        CustomerAddress::query()->create([
            'user_id' => $customer->id,
            'recipient_name' => $customer->name,
            'phone' => '+21620000000',
            'line_one' => '1 Rue des Fleurs',
            'city' => 'Tunis',
            'country_code' => 'TN',
            'is_default' => true,
        ]);
        CustomerChild::query()->create([
            'user_id' => $customer->id,
            'name' => 'Lina',
            'birth_date' => '2024-02-15',
            'gender' => 'female',
            'newborn' => false,
        ]);

        $this->actingAs($customer, 'customer')
            ->getJson('/api/v1/customer/profile')
            ->assertOk()
            ->assertJsonPath('data.name', 'Ada Customer')
            ->assertJsonPath('data.address', '1 Rue des Fleurs')
            ->assertJsonPath('data.children.0.birthDate', '2024-02-15')
            ->assertJsonPath('data.children.0.newborn', false);

        $this->putJson('/api/v1/customer/profile', [
            'name' => 'Ada Updated',
            'email' => 'ada.updated@example.com',
            'phone' => '+21621111111',
            'address' => '2 Rue des Jasmins',
            'city' => 'Sfax',
            'postal_code' => '3000',
            'children' => [
                ['name' => 'Baby', 'birth_date' => null, 'gender' => 'female', 'newborn' => true],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Ada Updated')
            ->assertJsonPath('data.email', 'ada.updated@example.com')
            ->assertJsonPath('data.phone', '+21621111111')
            ->assertJsonPath('data.address', '2 Rue des Jasmins')
            ->assertJsonPath('data.postalCode', '3000')
            ->assertJsonPath('data.children.0.newborn', true)
            ->assertJsonPath('data.children.0.birthDate', null);

        $this->assertDatabaseHas('customer_children', ['user_id' => $customer->id, 'name' => 'Baby', 'newborn' => true, 'birth_date' => null]);
        $this->assertDatabaseHas('customer_addresses', ['user_id' => $customer->id, 'line_one' => '2 Rue des Jasmins', 'phone' => '+21621111111', 'postal_code' => '3000']);
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'email' => 'ada.updated@example.com']);
        $this->assertDatabaseHas('users', ['id' => $otherCustomer->id, 'name' => 'Other Customer']);
    }

    public function test_profile_requires_a_birth_date_when_a_child_is_not_unborn(): void
    {
        $customer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);

        $this->actingAs($customer, 'customer')
            ->putJson('/api/v1/customer/profile', [
                'name' => 'Ada Customer',
                'email' => $customer->email,
                'children' => [
                    ['name' => 'Lina', 'gender' => 'female', 'newborn' => false],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['children.0.birth_date']);
    }

    public function test_profile_requires_phone_address_city_and_postal_code(): void
    {
        $customer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);

        $this->actingAs($customer, 'customer')
            ->putJson('/api/v1/customer/profile', [
                'name' => $customer->name,
                'email' => $customer->email,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['phone', 'address', 'city', 'postal_code']);
    }

    public function test_customer_cannot_update_their_profile_with_an_email_used_by_another_customer(): void
    {
        $customer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);
        $otherCustomer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);

        $this->actingAs($customer, 'customer')
            ->putJson('/api/v1/customer/profile', [
                'name' => $customer->name,
                'email' => $otherCustomer->email,
                'phone' => '+216 20 000 000',
                'address' => '1 Rue des Fleurs',
                'city' => 'Tunis',
                'postal_code' => '1000',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->assertDatabaseHas('users', ['id' => $customer->id, 'email' => $customer->email]);
    }

    public function test_customer_can_remove_their_last_child(): void
    {
        $customer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);
        $child = CustomerChild::query()->create([
            'user_id' => $customer->id,
            'name' => 'Lina',
            'birth_date' => '2024-02-15',
            'gender' => 'female',
            'newborn' => false,
        ]);

        $this->actingAs($customer, 'customer')
            ->putJson('/api/v1/customer/profile', [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => '+216 20 000 000',
                'address' => '1 Rue des Fleurs',
                'city' => 'Tunis',
                'postal_code' => '1000',
                'children' => [],
            ])
            ->assertOk()
            ->assertJsonCount(0, 'data.children');

        $this->assertDatabaseMissing('customer_children', ['id' => $child->id]);
    }
}
