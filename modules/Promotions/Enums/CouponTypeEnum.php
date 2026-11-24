<?php

namespace Modules\Promotions\Enums;

enum CouponTypeEnum: int
{
    case Percentage = 1;
    case Fixed = 2;

    /** @return array<int, int> */
    public static function values(): array
    {
        return array_map(static fn (self $type): int => $type->value, self::cases());
    }
}
