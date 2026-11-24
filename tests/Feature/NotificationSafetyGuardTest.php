<?php

namespace Tests\Feature;

use App\Mail\NotificationSafetyGuard;
use Illuminate\Mail\Events\MessageSending;
use LogicException;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class NotificationSafetyGuardTest extends TestCase
{
    public function test_guarded_environments_replace_to_and_remove_cc_and_bcc_recipients(): void
    {
        config()->set('app.env', 'local');
        config()->set('notification-safety.guarded_environments', ['local', 'test']);
        config()->set('notification-safety.email_fallback', 'mailbox@example.test');
        $message = (new Email)
            ->to('customer@example.test')
            ->cc('copied@example.test')
            ->bcc('hidden@example.test');

        (new NotificationSafetyGuard)(new MessageSending($message));

        $this->assertSame(['mailbox@example.test'], array_map(fn ($address): string => $address->getAddress(), $message->getTo()));
        $this->assertSame([], $message->getCc());
        $this->assertSame([], $message->getBcc());
    }

    public function test_non_guarded_environment_preserves_the_intended_recipient(): void
    {
        config()->set('app.env', 'production');
        config()->set('notification-safety.guarded_environments', [' local ', 'test']);
        config()->set('notification-safety.email_fallback', 'mailbox@example.test');
        $message = (new Email)->to('customer@example.test');

        (new NotificationSafetyGuard)(new MessageSending($message));

        $this->assertSame(['customer@example.test'], array_map(fn ($address): string => $address->getAddress(), $message->getTo()));
    }

    public function test_guarded_environment_with_missing_fallback_blocks_delivery(): void
    {
        config()->set('app.env', 'test');
        config()->set('notification-safety.guarded_environments', ['test']);
        config()->set('notification-safety.email_fallback', null);

        $this->expectException(LogicException::class);

        (new NotificationSafetyGuard)(new MessageSending((new Email)->to('customer@example.test')));
    }
}
