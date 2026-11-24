<?php

namespace Modules\Support\UseCases;

use Modules\Support\Models\ContactMessage;

final class MarkContactMessageReadUseCase
{
    public function execute(ContactMessage $message): void
    {
        if ($message->read_at === null) {
            $message->update(['read_at' => now()]);
        }
    }
}
