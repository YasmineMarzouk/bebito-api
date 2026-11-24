<?php

namespace Modules\Support\DTOs\Outputs;

use Modules\Support\Models\ContactMessage;

final readonly class ContactMessageOutput
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public ?string $phone,
        public string $subject,
        public string $message,
        public bool $isRead,
        public string $createdAt,
    ) {}

    public static function fromModel(ContactMessage $message): self
    {
        return new self(
            id: $message->id,
            name: $message->name,
            email: $message->email,
            phone: $message->phone,
            subject: $message->subject,
            message: $message->message,
            isRead: $message->read_at !== null,
            createdAt: $message->created_at->toISOString(),
        );
    }
}
