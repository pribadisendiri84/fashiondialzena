<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'code',
    'channel',
    'customer_name',
    'note',
    'subtotal',
    'cogs_total',
    'sold_at',
])]
class Order extends Model
{
    protected function casts(): array
    {
        return [
            'sold_at' => 'date',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function returns(): HasManyThrough
    {
        return $this->hasManyThrough(OrderReturn::class, OrderItem::class);
    }

    public function returnableQuantity(): int
    {
        return (int) $this->items->sum(fn (OrderItem $item) => $item->returnableQuantity());
    }

    protected function subtotalFormatted(): Attribute
    {
        return Attribute::get(fn () => 'Rp'.number_format((int) $this->subtotal, 0, ',', '.'));
    }

    protected function grossProfit(): Attribute
    {
        $refund = (int) $this->items->sum(fn (OrderItem $item) => $item->returns->sum('refund_amount'));
        $cogsReversed = (int) $this->items->sum(fn (OrderItem $item) => $item->returns->sum('cogs_reversed'));

        return Attribute::get(fn () => ((int) $this->subtotal - $refund) - ((int) $this->cogs_total - $cogsReversed));
    }

    protected function grossProfitFormatted(): Attribute
    {
        return Attribute::get(fn () => 'Rp'.number_format($this->gross_profit, 0, ',', '.'));
    }
}
