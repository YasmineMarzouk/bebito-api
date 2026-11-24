<?php

namespace Modules\Orders\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Enums\OrderManagementNotificationTypeEnum;
use Modules\Orders\Models\Order;

final class OrderManagementMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly Order $order, public readonly OrderManagementNotificationTypeEnum $type)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: match ($this->type) {
            OrderManagementNotificationTypeEnum::Confirmed => 'Your BÃ©bito order is confirmed',
            OrderManagementNotificationTypeEnum::Updated => 'Your BÃ©bito order has been updated',
            OrderManagementNotificationTypeEnum::Cancelled => 'Your BÃ©bito order has been cancelled',
        });
    }

    public function content(): Content
    {
        return new Content(view: 'mail.order-management', with: [
            'order' => $this->order,
            'heading' => match ($this->type) {
                OrderManagementNotificationTypeEnum::Confirmed => 'Your order is confirmed',
                OrderManagementNotificationTypeEnum::Updated => 'Your order has been updated',
                OrderManagementNotificationTypeEnum::Cancelled => 'Your order has been cancelled',
            },
            'message' => match ($this->type) {
                OrderManagementNotificationTypeEnum::Confirmed => 'Your order is confirmed and ready for delivery.',
                OrderManagementNotificationTypeEnum::Updated => 'Your order details have been updated. The total below is the current amount due on delivery.',
                OrderManagementNotificationTypeEnum::Cancelled => 'Your order has been cancelled. No cash-on-delivery payment is due.',
            },
            'total' => number_format((int) $this->order->total_amount / 1000, 3, '.', ',').' '.$this->order->currency_code,
        ]);
    }
}
