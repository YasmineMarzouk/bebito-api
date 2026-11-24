<?php

namespace Modules\Core\Enums;

enum PermissionEnum: int
{
    case Read = 1;
    case Write = 2;
    case Execute = 3;

    public static function fromRouteParameter(string $permission): ?self
    {
        return match ($permission) {
            'read' => self::Read,
            'write' => self::Write,
            'execute' => self::Execute,
            default => null,
        };
    }
}
