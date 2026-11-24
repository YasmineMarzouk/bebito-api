<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

class PackTranslation extends BaseModel
{
    protected $fillable = [
        'pack_id',
        'locale_code',
        'name',
        'slug',
        'short_description',
        'description',
        'meta_title',
        'meta_description',
    ];

    public function pack(): BelongsTo
    {
        return $this->belongsTo(Pack::class);
    }
}
