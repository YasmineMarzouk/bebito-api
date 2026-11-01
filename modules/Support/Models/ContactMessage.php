<?php
namespace Modules\Support\Models;
use Modules\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
class ContactMessage extends BaseModel { use SoftDeletes; protected $casts = ['read_at' => 'datetime', 'archived_at' => 'datetime']; }
