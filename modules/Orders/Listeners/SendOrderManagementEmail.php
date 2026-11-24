<?php

namespace Modules\Orders\Listeners;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Mail;
use Modules\Orders\Events\OrderManagementNotification;
use Modules\Orders\Mail\OrderManagementMail;
use Modules\Orders\Models\Order;

final class SendOrderManagementEmail implements ShouldQueueAfterCommit
{
    public int $tries = 1;

    public function handle(OrderManagementNotification $event): void
    {
        $order = Order::query()->find($event->orderId);

        if ($order !== null) {
            Mail::to($order->customer_email)->queue(new OrderManagementMail($order, $event->type));
        }
    }
}
