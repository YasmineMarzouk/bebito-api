<?php
namespace Modules\Customer\Models;
use Modules\Core\Models\BaseModel;
class CustomerAddress extends BaseModel { protected $casts = ['is_default' => 'boolean']; }
