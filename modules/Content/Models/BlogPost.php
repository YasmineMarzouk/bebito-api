<?php

namespace Modules\Content\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;

class BlogPost extends BaseModel
{
    use SoftDeletes;

    protected $casts = ['published_at' => 'datetime'];
}
