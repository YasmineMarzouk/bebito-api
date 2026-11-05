<?php

namespace Modules\Core\Models;

class Locale extends BaseModel
{
    protected $primaryKey = 'code';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $casts = ['is_active' => 'boolean', 'is_default' => 'boolean'];
}
