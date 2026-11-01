<?php
namespace Modules\Catalog\Models;
use Modules\Core\Models\BaseModel;
class Collection extends BaseModel { protected $casts = ['is_active' => 'boolean', 'is_featured' => 'boolean']; }
