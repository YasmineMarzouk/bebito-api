<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Section extends BaseModel
{
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'section_permission')->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }
}
