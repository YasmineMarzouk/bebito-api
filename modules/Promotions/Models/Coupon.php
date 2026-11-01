<?php
namespace Modules\Promotions\Models;
use Modules\Core\Models\BaseModel;
class Coupon extends BaseModel { protected $casts = ['is_active' => 'boolean']; }
