<?php

namespace Modules\Customer\Listeners;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Mail;
use Modules\Customer\Events\CustomerRegistered;
use Modules\Customer\Mail\WelcomeCustomerMail;

final class SendWelcomeEmail implements ShouldQueueAfterCommit
{
    public int $tries = 1;

    public function handle(CustomerRegistered $event): void
    {
        $customer = User::query()->find($event->customerId);

        if ($customer === null) {
            return;
        }

        Mail::to($customer->email)->queue(new WelcomeCustomerMail($customer->name));
    }
}
