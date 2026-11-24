<?php

namespace Modules\Auth\Enums;

enum AuthenticationContextEnum: string
{
    case Admin = 'admin';
    case Customer = 'customer';

    public function guard(): string
    {
        return $this->value;
    }
}
