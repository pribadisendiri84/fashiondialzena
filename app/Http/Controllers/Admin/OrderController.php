<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesDateRange;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\InventoryLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class OrderController extends Controller
{
    use ResolvesDateRange;

    public function index(Request $request)
    {
        $range = $this->dateRange($request);

        $orders = Order::query()
            ->with(['items.product', 'items.variant', 'items.returns'])
            ->whereBetween('sold_at', [$range['from'], $range['to']])
            ->latest('sold_at')
            ->latest('id')
            ->get();

        $items = $orders->pluck('items')->flatten();
        $returns = $items->pluck('returns')->flatten();

        $revenue = (int) $orders->sum('subtotal');
        $refund = (int) $returns->sum('refund_amount');
        $cogs = (int) $orders->sum('cogs_total');
        $cogsReversed = (int) $returns->sum('cogs_reversed');

        $search = trim((string) $request->input('q'));
        $visibleOrders = $search === ''
            ? $orders
            : $orders->filter(fn (Order $order) => $this->matchesSearch($order, $search))->values();

        return view('admin.sales.index', array_merge($range, [
            'orders' => $visibleOrders,
            'search' => $search,
            'variants' => ProductVariant::query()
                ->with('product')
                ->where('is_active', true)
                ->orderBy('sku')
                ->get(),
            'orderCount' => $orders->count(),
            'visibleCount' => $visibleOrders->count(),
            'soldQty' => (int) $items->sum('quantity'),
            'returnedQty' => (int) $returns->sum('quantity'),
            'revenue' => $revenue,
            'refund' => $refund,
            'cogs' => $cogs,
            'gross' => ($revenue - $refund) - ($cogs - $cogsReversed),
        ]));
    }

    private function matchesSearch(Order $order, string $search): bool
    {
        $haystack = collect([
            $order->code,
            $order->channel,
            $order->customer_name,
            $order->note,
        ])->merge($order->items->flatMap(fn (OrderItem $item) => [
            $item->product?->name,
            $item->variant?->sku,
            $item->variant?->label,
        ]))->filter()->implode(' ');

        return str_contains(mb_strtolower($haystack), mb_strtolower($search));
    }

    public function store(Request $request, InventoryLedger $ledger)
    {
        $data = $request->validate([
            'sold_at' => ['required', 'date'],
            'channel' => ['required', 'in:whatsapp,website,shopee,tokopedia,offline,lainnya'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_variant_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('product_variants', 'id')->where('is_active', true),
            ],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['nullable'],
        ]);

        try {
            $order = DB::transaction(function () use ($data, $ledger) {
                $variantIds = collect($data['items'])
                    ->pluck('product_variant_id')
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values();

                // Lock every SKU in a stable order before checking or changing stock.
                $variants = ProductVariant::query()
                    ->with('product')
                    ->whereIn('id', $variantIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $preparedItems = collect($data['items'])->map(function (array $row) use ($variants) {
                    $variant = $variants->get((int) $row['product_variant_id']);
                    $quantity = (int) $row['quantity'];
                    $unitPrice = isset($row['unit_price']) && $row['unit_price'] !== ''
                        ? (int) preg_replace('/\D+/', '', (string) $row['unit_price'])
                        : (int) $variant->sell_price;

                    if ($unitPrice <= 0) {
                        throw new InvalidArgumentException('Harga jual SKU '.$variant->sku.' harus lebih dari Rp0.');
                    }

                    if ($quantity > (int) $variant->stock) {
                        throw new InvalidArgumentException('Stok SKU '.$variant->sku.' hanya '.$variant->stock.'.');
                    }

                    return [
                        'variant' => $variant,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'unit_cost' => (int) $variant->cost_price,
                    ];
                });

                $order = Order::query()->create([
                    'code' => 'TMP-'.uniqid(),
                    'channel' => $data['channel'],
                    'customer_name' => $data['customer_name'] ?? null,
                    'note' => $data['note'] ?? null,
                    'subtotal' => (int) $preparedItems->sum(fn ($item) => $item['unit_price'] * $item['quantity']),
                    'cogs_total' => (int) $preparedItems->sum(fn ($item) => $item['unit_cost'] * $item['quantity']),
                    'sold_at' => $data['sold_at'],
                ]);

                $order->update([
                    'code' => 'ORD-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
                ]);

                foreach ($preparedItems as $prepared) {
                    /** @var ProductVariant $variant */
                    $variant = $prepared['variant'];
                    $item = $order->items()->create([
                        'product_id' => $variant->product_id,
                        'product_variant_id' => $variant->id,
                        'quantity' => $prepared['quantity'],
                        'unit_price' => $prepared['unit_price'],
                        'unit_cost' => $prepared['unit_cost'],
                        'total' => $prepared['unit_price'] * $prepared['quantity'],
                        'cogs_total' => $prepared['unit_cost'] * $prepared['quantity'],
                    ]);

                    $ledger->issue(
                        $variant,
                        $prepared['quantity'],
                        $prepared['unit_cost'],
                        'order_item',
                        $item->id,
                        $data['sold_at'],
                        $order->code
                    );
                }

                return $order;
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors([$e->getMessage()])->withInput();
        }

        return redirect()->route('admin.sales.index')->with(
            'ok',
            'Penjualan '.$order->code.' tercatat dengan '.$order->items()->count().' item.'
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
