<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'product_variant_id',
    'type',
    'quantity',
    'unit_cost',
    'stock_after',
    'reference_type',
    'reference_id',
    'note',
    'moved_at',
])]
class StockMovement extends Model
{
    protected function casts(): array
    {
        return [
            'moved_at' => 'date',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
