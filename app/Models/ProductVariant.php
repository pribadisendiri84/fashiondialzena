<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'product_id',
    'sku',
    'color',
    'size',
    'stock',
    'cost_price',
    'sell_price',
    'is_active',
])]
class ProductVariant extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $variant) {
            $variant->sku = strtoupper(trim((string) $variant->sku));
            $variant->color = trim((string) $variant->color);
            $variant->size = trim((string) $variant->size);
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockIns(): HasMany
    {
        return $this->hasMany(StockIn::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function hasHistory(): bool
    {
        return $this->orderItems()->exists()
            || $this->stockIns()->exists()
            || $this->movements()->exists();
    }

    protected function label(): Attribute
    {
        return Attribute::get(function () {
            $parts = array_filter([$this->color, $this->size]);
            $variant = $parts ? implode(' / ', $parts) : 'Default';

            return $this->sku.' — '.$variant;
        });
    }

    protected function sellPriceFormatted(): Attribute
    {
        return Attribute::get(fn () => 'Rp'.number_format((int) $this->sell_price, 0, ',', '.'));
    }

    protected function costPriceFormatted(): Attribute
    {
        return Attribute::get(fn () => 'Rp'.number_format((int) $this->cost_price, 0, ',', '.'));
    }
}
