<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesDateRange;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use App\Models\StockIn;
use App\Services\InventoryLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockInController extends Controller
{
    use ResolvesDateRange;

    public function index(Request $request)
    {
        $range = $this->dateRange($request);

        $entries = StockIn::query()
            ->with('variant.product')
            ->whereBetween('received_at', [$range['from'], $range['to']])
            ->latest('received_at')
            ->latest('id')
            ->get();

        return view('admin.stock-ins.index', array_merge($range, [
            'entries' => $entries,
            'variants' => ProductVariant::query()->with('product')->where('is_active', true)->orderBy('sku')->get(),
            'entryCount' => $entries->count(),
            'qty' => (int) $entries->sum('quantity'),
            'stockValue' => (int) $entries->sum(fn ($entry) => $entry->quantity * $entry->unit_cost),
            'selectedVariantId' => $request->integer('variant_id') ?: null,
        ]));
    }

    public function store(Request $request, InventoryLedger $ledger)
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'received_at' => ['required', 'date'],
            'unit_cost' => ['nullable'],
            'source' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $variant = ProductVariant::query()->with('product')->findOrFail($data['product_variant_id']);
        $unitCost = isset($data['unit_cost']) && $data['unit_cost'] !== ''
            ? (int) preg_replace('/\D+/', '', (string) $data['unit_cost'])
            : (int) $variant->cost_price;

        DB::transaction(function () use ($data, $variant, $unitCost, $ledger) {
            $entry = StockIn::query()->create([
                'product_variant_id' => $variant->id,
                'quantity' => $data['quantity'],
                'unit_cost' => $unitCost,
                'source' => $data['source'] ?? null,
                'note' => $data['note'] ?? null,
                'received_at' => $data['received_at'],
            ]);

            $ledger->receive(
                $variant,
                (int) $data['quantity'],
                $unitCost,
                'stock_in',
                $entry->id,
                $data['received_at'],
                $entry->source
            );
        });

        return redirect()->route('admin.stock-ins.index')->with(
            'ok',
            'Stok SKU '.$variant->sku.' ditambah '.$data['quantity'].'. Sisa sekarang '.$variant->fresh()->stock.'.'
        );
    }

    public function destroy(StockIn $stockIn, InventoryLedger $ledger)
    {
        $variant = $stockIn->variant;

        try {
            DB::transaction(function () use ($stockIn, $variant, $ledger) {
                $ledger->reverseReceive(
                    $variant,
                    (int) $stockIn->quantity,
                    (int) $stockIn->unit_cost,
                    'stock_in',
                    $stockIn->id,
                    now()->toDateString(),
                    'Hapus stok masuk'
                );
                $stockIn->delete();
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors([$e->getMessage()]);
        }

        return redirect()->route('admin.stock-ins.index')->with('ok', 'Catatan stok masuk dihapus. Stok dikurangi kembali.');
    }
}
