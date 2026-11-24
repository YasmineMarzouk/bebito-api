<?php

namespace Modules\Support\UseCases;

use Modules\Support\Models\ContactMessage;

final class DeleteContactMessageUseCase
{
    public function execute(ContactMessage $message): void
    {
        $message->delete();
    }
}
