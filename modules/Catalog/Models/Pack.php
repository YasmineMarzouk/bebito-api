<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Catalog\Enums\ProductGenderEnum;
use Modules\Catalog\Enums\ProductStatusEnum;
use Modules\Core\Models\Asset;
use Modules\Core\Models\BaseModel;

class Pack extends BaseModel
{
    use SoftDeletes;

    protected $fillable = [
        'status',
        'gender',
        'sku',
        'price_amount',
        'compare_at_price_amount',
        'discount_percentage',
        'low_stock_threshold',
        'first_published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatusEnum::class,
            'gender' => ProductGenderEnum::class,
            'price_amount' => 'integer',
            'compare_at_price_amount' => 'integer',
            'discount_percentage' => 'float',
            'low_stock_threshold' => 'integer',
            'first_published_at' => 'datetime',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PackTranslation::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(PackVariant::class)->where('is_active', true);
    }

    public function allVariants(): HasMany
    {
        return $this->hasMany(PackVariant::class);
    }

    public function components(): HasMany
    {
        return $this->hasMany(PackComponent::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->using(CategoryPack::class)->withTimestamps();
    }

    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class)->using(CollectionPack::class)->withPivot('sort_order')->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->using(PackTag::class)->withTimestamps();
    }

    public function assets(): MorphMany
    {
        return $this->morphMany(Asset::class, 'resource')->orderBy('sort_order')->orderBy('id');
    }

    public function getMorphClass(): string
    {
        return 'pack';
    }
}
