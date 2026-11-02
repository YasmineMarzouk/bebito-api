<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

class CollectionTranslation extends BaseModel
{
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }
}
