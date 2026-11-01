<?php
namespace Modules\Content\Models;
use Modules\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
class BlogPost extends BaseModel { use SoftDeletes; protected $casts = ['published_at' => 'datetime']; }
