<?php

namespace Modules\Core\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends BaseModel
{
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->using(RoleUser::class)->withTimestamps();
    }
}
