We received your order {{ $order->number }}.

Payment: Cash on Delivery

@foreach ($items as $item)
{{ $item['name'] }} — Size: {{ $item['size'] }}, Color: {{ $item['color'] }}, Quantity: {{ $item['quantity'] }}, Total: {{ $item['total'] }}
@endforeach

Subtotal: {{ $subtotal }}
Shipping fee: {{ $shipping }}
Total: {{ $total }}
