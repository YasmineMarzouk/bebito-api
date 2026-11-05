<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends BaseModel
{
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'section_permission')->withTimestamps();
    }
}
