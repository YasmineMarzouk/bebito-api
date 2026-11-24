<?php

namespace Modules\Support\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Models\BaseModel;

final class NewsletterSubscription extends BaseModel
{
    protected $table = 'newsletter_subscription';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
