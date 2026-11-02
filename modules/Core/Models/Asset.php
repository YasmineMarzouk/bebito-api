<?php

namespace Modules\Core\Models;

class Asset extends BaseModel
{
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'sort_order' => 'integer',
            'is_primary' => 'boolean',
        ];
    }
}
