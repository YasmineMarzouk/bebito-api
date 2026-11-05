<?php

namespace Modules\Core\Enums;

enum SectionEnum: string
{
    case Analytics = 'analytics';
    case Notification = 'notification';
    case Products = 'products';
    case Categories = 'categories';
    case Collections = 'collections';
    case Coupons = 'coupons';
    case Orders = 'orders';
    case Customers = 'customers';
    case Blog = 'blog';
    case Messages = 'messages';
    case Users = 'users';
    case Settings = 'settings';
}
