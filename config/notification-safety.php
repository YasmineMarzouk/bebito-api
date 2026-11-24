<?php

return [
    'guarded_environments' => array_values(array_filter(array_map(
        static fn (string $environment): string => trim($environment),
        explode(',', (string) env('NOTIFICATION_SAFETY_GUARDED_ENVS', 'dev,local,test,testing')),
    ), static fn (string $environment): bool => $environment !== '')),

    'email_fallback' => env('NOTIFICATION_SAFETY_EMAIL_FALLBACK'),
];
