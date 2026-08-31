<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'created_by',
    'order_item_id',
    'quantity',
    'reason',
    'restocked',
    'condition',
    'refund_amount',
    'cogs_reversed',
    'note',
    'returned_at',
])]
class OrderReturn extends Model
{
    protected function casts(): array
    {
        return [
            'returned_at' => 'date',
            'restocked' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    protected function refundAmountFormatted(): Attribute
    {
        return Attribute::get(fn () => 'Rp'.number_format((int) $this->refund_amount, 0, ',', '.'));
    }
}
