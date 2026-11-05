<?php

namespace Modules\Orders\DTOs\Outputs;

use Modules\Orders\Models\Order;

final readonly class CheckoutOrderOutput
{
    public function __construct(public int $id, public string $number, public int $totalAmount, public string $currencyCode) {}

    public static function fromModel(Order $order): self
    {
        return new self($order->id, $order->number, $order->total_amount, $order->currency_code);
    }
}
