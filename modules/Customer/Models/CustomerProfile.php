<?php
namespace Modules\Customer\Models;
use Modules\Core\Models\BaseModel;
class CustomerProfile extends BaseModel { protected $casts = ['marketing_opt_in' => 'boolean']; }
