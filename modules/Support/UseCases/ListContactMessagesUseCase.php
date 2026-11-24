<?php

namespace Modules\Support\UseCases;

use Modules\Support\DTOs\Outputs\ContactMessageOutput;
use Modules\Support\Models\ContactMessage;

final class ListContactMessagesUseCase
{
    /** @return array<int, ContactMessageOutput> */
    public function execute(): array
    {
        return ContactMessage::query()
            ->whereNull('archived_at')
            ->orderByDesc('id')
            ->get()
            ->map(ContactMessageOutput::fromModel(...))
            ->all();
    }
}
