<?php

namespace Modules\Support\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Core\Models\BaseModel;

class ContactMessage extends BaseModel
{
    use SoftDeletes;

    protected $casts = ['read_at' => 'datetime', 'archived_at' => 'datetime'];
}
