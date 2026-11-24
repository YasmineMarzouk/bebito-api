<?php

namespace Modules\Customer\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class WelcomeCustomerMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly string $customerName)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to Bébito');
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.welcome-customer',
            text: 'mail.welcome-customer-text',
            with: [
                'customerName' => $this->customerName,
                'storeUrl' => rtrim((string) config('notifications.store_url'), '/'),
            ],
        );
    }
}
