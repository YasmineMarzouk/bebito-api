<?php
namespace Modules\Content\Models;
use Modules\Core\Models\BaseModel;
class HomepageSection extends BaseModel { protected $casts = ['content' => 'array', 'is_active' => 'boolean']; }
