<?php

namespace Modules\Promotions\Enums;

enum CouponTypeEnum: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';
}
