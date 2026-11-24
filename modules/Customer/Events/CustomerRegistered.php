<?php

namespace Modules\Customer\Events;

final readonly class CustomerRegistered
{
    public function __construct(public int $customerId) {}
}
