<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use InvalidArgumentException;

class InventoryLedger
{
    public function receive(ProductVariant $variant, int $quantity, int $unitCost, string $referenceType, int $referenceId, $movedAt, ?string $note = null): StockMovement
    {
        $variant = $this->locked($variant);

        $oldStock = (int) $variant->stock;
        $oldCost = (int) $variant->cost_price;
        $newStock = $oldStock + $quantity;
        $newCost = $newStock > 0
            ? (int) round((($oldStock * $oldCost) + ($quantity * $unitCost)) / $newStock)
            : $oldCost;

        $variant->forceFill([
            'stock' => $newStock,
            'cost_price' => $newCost,
        ])->save();

        return $this->record($variant, 'in', $quantity, $unitCost, $referenceType, $referenceId, $movedAt, $note);
    }

    public function issue(ProductVariant $variant, int $quantity, int $unitCost, string $referenceType, int $referenceId, $movedAt, ?string $note = null): StockMovement
    {
        $variant = $this->locked($variant);

        if ($quantity > (int) $variant->stock) {
            throw new InvalidArgumentException('Stok SKU '.$variant->sku.' hanya '.$variant->stock.'.');
        }

        $variant->decrement('stock', $quantity);

        return $this->record($variant->fresh(), 'sale', -$quantity, $unitCost, $referenceType, $referenceId, $movedAt, $note);
    }

    public function restock(ProductVariant $variant, int $quantity, int $unitCost, string $referenceType, int $referenceId, $movedAt, ?string $note = null): StockMovement
    {
        $variant = $this->locked($variant);
        $variant->increment('stock', $quantity);

        return $this->record($variant->fresh(), 'return', $quantity, $unitCost, $referenceType, $referenceId, $movedAt, $note);
    }

    public function reverseReceive(ProductVariant $variant, int $quantity, int $unitCost, string $referenceType, int $referenceId, $movedAt, ?string $note = null): StockMovement
    {
        $variant = $this->locked($variant);

        if ($quantity > (int) $variant->stock) {
            throw new InvalidArgumentException('Tidak bisa hapus. Stok SKU sekarang lebih kecil dari qty masuk.');
        }

        $oldStock = (int) $variant->stock;
        $oldCost = (int) $variant->cost_price;
        $remainingQty = $oldStock - $quantity;
        $remainingValue = ($oldStock * $oldCost) - ($quantity * $unitCost);
        $newCost = $remainingQty > 0 ? (int) max(0, round($remainingValue / $remainingQty)) : $oldCost;

        $variant->forceFill([
            'stock' => $remainingQty,
            'cost_price' => $newCost,
        ])->save();

        return $this->record($variant, 'reversal', -$quantity, $unitCost, $referenceType, $referenceId, $movedAt, $note);
    }

    public function reverseIssue(ProductVariant $variant, int $quantity, int $unitCost, string $referenceType, int $referenceId, $movedAt, ?string $note = null): StockMovement
    {
        $variant = $this->locked($variant);
        $variant->increment('stock', $quantity);

        return $this->record($variant->fresh(), 'reversal', $quantity, $unitCost, $referenceType, $referenceId, $movedAt, $note);
    }

    public function reverseRestock(ProductVariant $variant, int $quantity, int $unitCost, string $referenceType, int $referenceId, $movedAt, ?string $note = null): StockMovement
    {
        $variant = $this->locked($variant);

        if ($quantity > (int) $variant->stock) {
            throw new InvalidArgumentException('Tidak bisa hapus retur. Stok SKU sekarang lebih kecil dari qty retur.');
        }

        $variant->decrement('stock', $quantity);

        return $this->record($variant->fresh(), 'reversal', -$quantity, $unitCost, $referenceType, $referenceId, $movedAt, $note);
    }

    private function locked(ProductVariant $variant): ProductVariant
    {
        return ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
    }

    private function record(
        ProductVariant $variant,
        string $type,
        int $quantity,
        int $unitCost,
        string $referenceType,
        int $referenceId,
        $movedAt,
        ?string $note
    ): StockMovement {
        return StockMovement::query()->create([
            'product_variant_id' => $variant->id,
            'type' => $type,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'stock_after' => (int) $variant->stock,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
            'moved_at' => $movedAt,
        ]);
    }
}
