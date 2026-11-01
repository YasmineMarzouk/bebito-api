<?php
namespace Modules\Catalog\Models;
use Modules\Core\Models\BaseModel;
class ProductVariant extends BaseModel { protected $casts = ['attributes' => 'array']; }
