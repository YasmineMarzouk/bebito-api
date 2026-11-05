<?php

namespace Modules\Core\Enums;

enum PermissionEnum: string
{
    case Read = 'read';
    case Write = 'write';
    case Execute = 'execute';
}
