<?php

namespace Modules\Orders\DTOs\Outputs;

use Modules\Orders\Models\Order;

final readonly class OrderListOutput
{
    public function __construct(public int $id, public ?int $userId, public string $number, public string $customerName, public string $customerEmail, public string $status, public string $paymentMethodCode, public int $totalAmount, public string $createdAt) {}

    public static function fromModel(Order $order): self
    {
        return new self($order->id, $order->user_id, $order->number, $order->customer_name, $order->customer_email, $order->status, $order->payment_method_code, $order->total_amount, $order->created_at->toDateString());
    }
}
