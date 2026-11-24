<?php

namespace Modules\Orders\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;

final class OrderReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly Order $order)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'We received your Bébito order '.$this->order->number);
    }

    public function content(): Content
    {
        $this->order->loadMissing('items');

        return new Content(
            view: 'mail.order-received',
            text: 'mail.order-received-text',
            with: [
                'order' => $this->order,
                'items' => $this->items(),
                'subtotal' => $this->money((int) $this->order->subtotal_amount),
                'shipping' => $this->money((int) $this->order->shipping_amount),
                'total' => $this->money((int) $this->order->total_amount),
            ],
        );
    }

    /** @return array<int, array{name: string, sku: string, size: string, color: string, quantity: int, unitPrice: string, total: string}> */
    private function items(): array
    {
        return $this->order->items->map(function (OrderItem $item): array {
            $attributes = is_array($item->variant_attributes) ? $item->variant_attributes : [];

            return [
                'name' => (string) $item->product_name,
                'sku' => (string) $item->sku,
                'size' => $this->attribute($attributes['size'] ?? null),
                'color' => $this->attribute($attributes['color'] ?? null),
                'quantity' => (int) $item->quantity,
                'unitPrice' => $this->money((int) $item->unit_price_amount),
                'total' => $this->money((int) $item->total_amount),
            ];
        })->all();
    }

    private function attribute(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '—';
    }

    private function money(int $amount): string
    {
        return number_format($amount / 1000, 3, '.', ',').' '.$this->order->currency_code;
    }
}
