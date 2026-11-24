<x-mail.layout title="Order received">
    <h1 style="margin:0 0 16px;font-size:26px;color:#6a4b3b;">We received your order</h1>
    <p>Thank you for your order, {{ $order->customer_name }}. Your order reference is <strong>{{ $order->number }}</strong>.</p>
    <p>Payment will be collected by <strong>Cash on Delivery</strong>.</p>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;margin:24px 0;font-size:14px;">
        <thead>
            <tr>
                <th align="left" style="padding:10px 6px;border-bottom:1px solid #ead8c8;">Item</th>
                <th align="right" style="padding:10px 6px;border-bottom:1px solid #ead8c8;">Qty</th>
                <th align="right" style="padding:10px 6px;border-bottom:1px solid #ead8c8;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $item)
                <tr>
                    <td style="padding:12px 6px;border-bottom:1px solid #f2ebe5;">
                        <strong>{{ $item['name'] }}</strong><br>
                        <span style="color:#786c64;">Size: {{ $item['size'] }} · Color: {{ $item['color'] }} · {{ $item['unitPrice'] }} each</span>
                    </td>
                    <td align="right" style="padding:12px 6px;border-bottom:1px solid #f2ebe5;">{{ $item['quantity'] }}</td>
                    <td align="right" style="padding:12px 6px;border-bottom:1px solid #f2ebe5;">{{ $item['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:15px;">
        <tr><td style="padding:4px 0;">Subtotal</td><td align="right">{{ $subtotal }}</td></tr>
        <tr><td style="padding:4px 0;">Shipping fee</td><td align="right">{{ $shipping }}</td></tr>
        <tr><td style="padding:12px 0 0;font-size:18px;font-weight:700;">Total</td><td align="right" style="padding:12px 0 0;font-size:18px;font-weight:700;">{{ $total }}</td></tr>
    </table>
    <p style="margin:28px 0 0;">We’ll keep you updated as your order moves forward.</p>
</x-mail.layout>
