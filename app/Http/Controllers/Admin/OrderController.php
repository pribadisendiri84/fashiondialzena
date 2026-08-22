<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\InventoryLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $query = Order::query()
            ->with(['items.product', 'items.variant', 'items.returns'])
            ->latest('sold_at')
            ->latest('id');

        if ($month) {
            $query->whereYear('sold_at', substr($month, 0, 4))
                ->whereMonth('sold_at', substr($month, 5, 2));
        }

        $orders = $query->get();
        $items = $orders->pluck('items')->flatten();
        $returns = $items->pluck('returns')->flatten();

        $revenue = (int) $orders->sum('subtotal');
        $refund = (int) $returns->sum('refund_amount');
        $cogs = (int) $orders->sum('cogs_total');
        $cogsReversed = (int) $returns->sum('cogs_reversed');

        return view('admin.sales.index', [
            'orders' => $orders,
            'variants' => ProductVariant::query()
                ->with('product')
                ->where('is_active', true)
                ->orderBy('sku')
                ->get(),
            'month' => $month,
            'soldQty' => (int) $items->sum('quantity'),
            'returnedQty' => (int) $returns->sum('quantity'),
            'revenue' => $revenue,
            'refund' => $refund,
            'cogs' => $cogs,
            'gross' => ($revenue - $refund) - ($cogs - $cogsReversed),
        ]);
    }

    public function store(Request $request, InventoryLedger $ledger)
    {
        $data = $request->validate([
            'product_variant_id' => ['required', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'sold_at' => ['required', 'date'],
            'channel' => ['required', 'in:whatsapp,website,shopee,tokopedia,offline,lainnya'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
            'unit_price' => ['nullable'],
        ]);

        $variant = ProductVariant::query()->with('product')->findOrFail($data['product_variant_id']);
        $qty = (int) $data['quantity'];
        $unitPrice = isset($data['unit_price']) && $data['unit_price'] !== ''
            ? (int) preg_replace('/\D+/', '', (string) $data['unit_price'])
            : (int) $variant->sell_price;
        $unitCost = (int) $variant->cost_price;

        try {
            DB::transaction(function () use ($data, $variant, $qty, $unitPrice, $unitCost, $ledger) {
                $order = Order::query()->create([
                    'code' => 'TMP-'.uniqid(),
                    'channel' => $data['channel'],
                    'customer_name' => $data['customer_name'] ?? null,
                    'note' => $data['note'] ?? null,
                    'subtotal' => $unitPrice * $qty,
                    'cogs_total' => $unitCost * $qty,
                    'sold_at' => $data['sold_at'],
                ]);

                $order->update([
                    'code' => 'ORD-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
                ]);

                $item = $order->items()->create([
                    'product_id' => $variant->product_id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                    'total' => $unitPrice * $qty,
                    'cogs_total' => $unitCost * $qty,
                ]);

                $ledger->issue($variant, $qty, $unitCost, 'order_item', $item->id, $data['sold_at'], $order->code);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors([$e->getMessage()])->withInput();
        }

        return redirect()->route('admin.sales.index')->with(
            'ok',
            'Penjualan tercatat. SKU '.$variant->sku.' sisa '.$variant->fresh()->stock.'.'
        );
    }

    public function destroy(Order $order, InventoryLedger $ledger)
    {
        $order->load(['items.returns', 'items.variant']);

        if ($order->items->contains(fn (OrderItem $item) => $item->returns->isNotEmpty())) {
            return back()->withErrors(['Tidak bisa hapus. Sudah ada retur. Hapus retur dulu.']);
        }

        try {
            DB::transaction(function () use ($order, $ledger) {
                foreach ($order->items as $item) {
                    $ledger->reverseIssue(
                        $item->variant,
                        (int) $item->quantity,
                        (int) $item->unit_cost,
                        'order_item',
                        $item->id,
                        now()->toDateString(),
                        'Hapus '.$order->code
                    );
                }

                $order->delete();
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors([$e->getMessage()]);
        }

        return redirect()->route('admin.sales.index')->with('ok', 'Transaksi dihapus. Stok dikembalikan.');
    }
}
