<?php
namespace Modules\Catalog\Models;
use Modules\Core\Models\BaseModel;
class Category extends BaseModel { protected $casts = ['is_active' => 'boolean']; }
