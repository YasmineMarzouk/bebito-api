<?php

namespace Modules\Orders\Listeners;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Support\Facades\Mail;
use Modules\Orders\Events\OrderPlaced;
use Modules\Orders\Mail\OrderReceivedMail;
use Modules\Orders\Models\Order;

final class SendOrderReceivedEmail implements ShouldQueueAfterCommit
{
    public int $tries = 1;

    public function handle(OrderPlaced $event): void
    {
        $order = Order::query()->with('items')->find($event->orderId);

        if ($order === null) {
            return;
        }

        Mail::to($order->customer_email)->queue(new OrderReceivedMail($order));
    }
}
