<?php

namespace Modules\Core\Models;

class Country extends BaseModel
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = ['is_active' => 'boolean'];
}
