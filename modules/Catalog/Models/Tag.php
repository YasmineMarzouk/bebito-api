<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Core\Models\BaseModel;

class Tag extends BaseModel
{
    protected $fillable = ['name'];

    public function packs(): BelongsToMany
    {
        return $this->belongsToMany(Pack::class)->using(PackTag::class)->withTimestamps();
    }
}
