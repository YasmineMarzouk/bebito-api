<?php

namespace App\Mail;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use LogicException;

final class NotificationSafetyGuard
{
    public function __invoke(MessageSending $event): void
    {
        $guardedEnvironments = config('notification-safety.guarded_environments', []);

        if (! is_array($guardedEnvironments) || ! in_array((string) config('app.env'), $guardedEnvironments, true)) {
            return;
        }

        $fallback = config('notification-safety.email_fallback');

        if (! is_string($fallback) || filter_var($fallback, FILTER_VALIDATE_EMAIL) === false) {
            Log::critical('Outbound email blocked because the notification safety fallback is invalid.', [
                'environment' => config('app.env'),
            ]);

            throw new LogicException('Outbound email blocked because the notification safety fallback is invalid.');
        }

        $headers = $event->message->getHeaders();
        $headers->remove('Cc');
        $headers->remove('Bcc');
        $event->message->to($fallback);
    }
}
