<x-mail.layout title="A new collection is here">
    <h1 style="margin:0 0 16px;font-size:26px;color:#6a4b3b;">A new collection is here</h1>
    <p>Hello {{ $customerName }}, we’ve just published <strong>{{ $collectionName }}</strong>, a new Bébito collection selected with care for little ones.</p>
    <p style="margin:28px 0;">
        <a href="{{ $collectionUrl }}" style="display:inline-block;padding:12px 20px;background:#6a4b3b;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:700;">Discover the collection</a>
    </p>
    <p style="margin-bottom:0;">We hope you find something you love.</p>
</x-mail.layout>
