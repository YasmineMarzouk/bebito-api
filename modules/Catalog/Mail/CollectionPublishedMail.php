<?php

namespace Modules\Catalog\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Catalog\Models\Collection;

final class CollectionPublishedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly Collection $collection, public readonly string $customerName)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Discover our new '.$this->collection->name.' collection');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.collection-published',
            text: 'mail.collection-published-text',
            with: [
                'customerName' => $this->customerName,
                'collectionName' => $this->collection->name,
                'collectionUrl' => rtrim((string) config('notifications.store_url'), '/').'/collections/'.rawurlencode($this->collection->slug),
            ],
        );
    }
}
