<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;

class PackVariant extends BaseModel
{
    protected $fillable = ['pack_id', 'sku', 'attributes', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return ['attributes' => 'array', 'is_default' => 'boolean', 'is_active' => 'boolean'];
    }

    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }

    public function componentVariantMappings(): HasMany
    {
        return $this->hasMany(PackVariantComponentVariant::class);
    }
}
