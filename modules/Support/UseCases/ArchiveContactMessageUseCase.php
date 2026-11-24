<?php

namespace Modules\Support\UseCases;

use Modules\Support\Models\ContactMessage;

final class ArchiveContactMessageUseCase
{
    public function execute(ContactMessage $message): void
    {
        $message->update(['archived_at' => now()]);
    }
}
