<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'name',
    'category_id',
    'img_front',
    'img_back',
    'rating',
    'is_new',
    'is_best_seller',
    'is_featured',
    'is_active',
    'sort_order',
])]
class Product extends Model
{
    protected function casts(): array
    {
        return [
            'is_new' => 'boolean',
            'is_best_seller' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'rating' => 'decimal:1',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockIns(): HasManyThrough
    {
        return $this->hasManyThrough(StockIn::class, ProductVariant::class);
    }

    protected function stock(): Attribute
    {
        return Attribute::get(function () {
            if ($this->relationLoaded('variants')) {
                return (int) $this->variants->sum('stock');
            }

            return (int) $this->variants()->sum('stock');
        });
    }

    protected function price(): Attribute
    {
        return Attribute::get(function () {
            $variants = $this->relationLoaded('variants')
                ? $this->variants
                : $this->variants()->get();

            $active = $variants->where('is_active', true);

            return (int) ($active->min('sell_price') ?? $variants->min('sell_price') ?? 0);
        });
    }

    protected function priceFormatted(): Attribute
    {
        return Attribute::get(fn () => 'Rp'.number_format((int) $this->price, 0, ',', '.'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
