<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RoleUser extends Pivot
{
    protected $table = 'user_role';

    public $incrementing = false;
}
