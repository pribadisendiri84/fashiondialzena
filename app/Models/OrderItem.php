<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'order_id',
    'product_id',
    'product_variant_id',
    'quantity',
    'unit_price',
    'unit_cost',
    'total',
    'cogs_total',
])]
class OrderItem extends Model
{
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(OrderReturn::class);
    }

    public function returnedQuantity(): int
    {
        if ($this->relationLoaded('returns')) {
            return (int) $this->returns->sum('quantity');
        }

        return (int) $this->returns()->sum('quantity');
    }

    public function returnableQuantity(): int
    {
        return max(0, (int) $this->quantity - $this->returnedQuantity());
    }

    protected function totalFormatted(): Attribute
    {
        return Attribute::get(fn () => 'Rp'.number_format((int) $this->total, 0, ',', '.'));
    }
}
