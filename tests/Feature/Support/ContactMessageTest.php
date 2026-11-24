<?php

namespace Tests\Feature\Support;

use App\Models\User;
use Database\Seeders\StaffAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Enums\UserTypeEnum;
use Modules\Core\Models\Role;
use Modules\Customer\Models\CustomerAddress;
use Modules\Customer\Models\CustomerProfile;
use Modules\Support\Models\ContactMessage;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_submit_a_contact_message_with_a_phone_number_and_subject(): void
    {
        $this->postJson('/api/v1/contact-messages', [
            'name' => 'Meriem Ben Salem',
            'email' => 'MERIEM@example.test',
            'phone' => '+216 20 000 000',
            'subject' => 'Question about sizing',
            'message' => 'Could you help me choose the right size for a newborn?',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Your message has been sent.');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Meriem Ben Salem',
            'email' => 'meriem@example.test',
            'phone' => '+216 20 000 000',
            'subject' => 'Question about sizing',
        ]);
    }

    public function test_authorized_staff_can_read_archive_and_delete_contact_messages(): void
    {
        $staff = $this->ownerStaff();
        $archived = ContactMessage::query()->create([
            'name' => 'Meriem Ben Salem',
            'email' => 'meriem@example.test',
            'phone' => '+21620000000',
            'subject' => 'Question about sizing',
            'message' => 'Could you help me choose the right size for a newborn?',
        ]);
        $deleted = ContactMessage::query()->create([
            'name' => 'Amira Trabelsi',
            'email' => 'amira@example.test',
            'phone' => '+21621111111',
            'subject' => 'Order question',
            'message' => 'I need help with an order that I placed yesterday.',
        ]);

        $this->actingAs($staff, 'admin')
            ->getJson('/api/v1/admin/messages')
            ->assertOk()
            ->assertJsonPath('data.0.id', $deleted->id)
            ->assertJsonPath('data.1.phone', '+21620000000')
            ->assertJsonPath('data.1.isRead', false);

        $this->actingAs($staff, 'admin')
            ->patchJson('/api/v1/admin/messages/'.$archived->id.'/read')
            ->assertOk();
        $this->assertNotNull($archived->fresh()->read_at);

        $this->actingAs($staff, 'admin')
            ->patchJson('/api/v1/admin/messages/'.$archived->id.'/archive')
            ->assertOk();
        $this->assertNotNull($archived->fresh()->archived_at);

        $this->actingAs($staff, 'admin')
            ->deleteJson('/api/v1/admin/messages/'.$deleted->id)
            ->assertOk();
        $this->assertSoftDeleted('contact_messages', ['id' => $deleted->id]);
    }

    public function test_contact_message_details_do_not_update_a_customer_profile(): void
    {
        $customer = User::factory()->create(['user_type' => UserTypeEnum::Customer->value]);
        CustomerProfile::query()->create(['user_id' => $customer->id, 'phone' => '+216 20 000 000']);
        CustomerAddress::query()->create([
            'user_id' => $customer->id,
            'recipient_name' => $customer->name,
            'phone' => '+216 20 000 000',
            'line_one' => '1 Rue des Fleurs',
            'city' => 'Tunis',
            'postal_code' => '1000',
            'country_code' => 'TN',
            'is_default' => true,
        ]);

        $this->actingAs($customer, 'customer')->postJson('/api/v1/contact-messages', [
            'name' => 'Other Customer',
            'email' => 'other@example.test',
            'phone' => '+216 21 111 111',
            'subject' => 'Question about sizing',
            'message' => 'Could you help me choose the right size for a newborn?',
        ])->assertCreated();

        $this->assertDatabaseHas('customer_profiles', [
            'user_id' => $customer->id,
            'phone' => '+216 20 000 000',
        ]);
        $this->assertDatabaseHas('customer_addresses', [
            'user_id' => $customer->id,
            'line_one' => '1 Rue des Fleurs',
            'city' => 'Tunis',
            'postal_code' => '1000',
        ]);
        $this->assertDatabaseHas('contact_messages', [
            'email' => 'other@example.test',
            'phone' => '+216 21 111 111',
        ]);
    }

    private function ownerStaff(): User
    {
        $owner = Role::query()->create(['name' => 'owner', 'title' => 'Owner']);
        $this->seed(StaffAccessSeeder::class);
        $staff = User::factory()->create(['user_type' => UserTypeEnum::Staff->value]);
        $staff->roles()->attach($owner);

        return $staff;
    }
}
