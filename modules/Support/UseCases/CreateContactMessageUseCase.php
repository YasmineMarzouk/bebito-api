<?php

namespace Modules\Support\UseCases;

use Modules\Support\Models\ContactMessage;

final class CreateContactMessageUseCase
{
    /** @param array{name: string, email: string, phone: string, subject: string, message: string} $data */
    public function execute(array $data): void
    {
        ContactMessage::query()->create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'phone' => trim($data['phone']),
            'subject' => trim($data['subject']),
            'message' => trim($data['message']),
        ]);
    }
}
