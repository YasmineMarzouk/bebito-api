<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Modules\Core\Models\Asset;
use Modules\Core\Models\BaseModel;

class Collection extends BaseModel
{
    protected $casts = ['is_active' => 'boolean', 'is_featured' => 'boolean', 'sort_order' => 'integer'];

    public function translations(): HasMany
    {
        return $this->hasMany(CollectionTranslation::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->using(CollectionProduct::class)->withPivot('sort_order')->withTimestamps();
    }

    public function primaryAsset(): MorphOne
    {
        return $this->morphOne(Asset::class, 'resource')->where('is_primary', true);
    }

    public function getMorphClass(): string
    {
        return 'collection';
    }
}
