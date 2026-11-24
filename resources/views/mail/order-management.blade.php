<x-mail.layout :title="$heading">
    <h1 style="margin:0 0 16px;font-size:26px;color:#6a4b3b;">{{ $heading }}</h1>
    <p>Hello {{ $order->customer_name }},</p>
    <p>{{ $message }}</p>
    <p>Your order reference is <strong>{{ $order->number }}</strong>.</p>
    <p style="font-size:18px;font-weight:700;">Order total: {{ $total }}</p>
</x-mail.layout>
