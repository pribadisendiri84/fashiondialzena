<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Services\InventoryLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderReturnController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));

        $query = OrderReturn::query()
            ->with(['item.order', 'item.product', 'item.variant'])
            ->latest('returned_at')
            ->latest('id');

        if ($month) {
            $query->whereYear('returned_at', substr($month, 0, 4))
                ->whereMonth('returned_at', substr($month, 5, 2));
        }

        $returns = $query->get();

        $items = OrderItem::query()
            ->with(['order', 'product', 'variant', 'returns'])
            ->latest('id')
            ->limit(200)
            ->get()
            ->filter(fn (OrderItem $item) => $item->returnableQuantity() > 0)
            ->values();

        return view('admin.returns.index', [
            'returns' => $returns,
            'items' => $items,
            'month' => $month,
            'qty' => (int) $returns->sum('quantity'),
            'refund' => (int) $returns->sum('refund_amount'),
        ]);
    }

    public function store(Request $request, InventoryLedger $ledger)
    {
        $data = $request->validate([
            'order_item_id' => ['required', 'exists:order_items,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'returned_at' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', 'string', 'max:40'],
            'refund_amount' => ['nullable'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $item = OrderItem::query()->with(['order', 'variant'])->findOrFail($data['order_item_id']);
        $qty = (int) $data['quantity'];

        if ($qty > $item->returnableQuantity()) {
            return back()->withErrors([
                'Qty retur melebihi sisa yang bisa diretur ('.$item->returnableQuantity().').',
            ])->withInput();
        }

        $refund = isset($data['refund_amount']) && $data['refund_amount'] !== ''
            ? (int) preg_replace('/\D+/', '', (string) $data['refund_amount'])
            : (int) ($item->unit_price * $qty);

        $restocked = $request->boolean('restocked', true);
        $cogsReversed = $restocked ? (int) $item->unit_cost * $qty : 0;

        try {
            DB::transaction(function () use ($data, $item, $qty, $refund, $restocked, $cogsReversed, $ledger) {
                $return = OrderReturn::query()->create([
                    'order_item_id' => $item->id,
                    'quantity' => $qty,
                    'reason' => $data['reason'] ?? null,
                    'restocked' => $restocked,
                    'condition' => $data['condition'] ?? null,
                    'refund_amount' => $refund,
                    'cogs_reversed' => $cogsReversed,
                    'note' => $data['note'] ?? null,
                    'returned_at' => $data['returned_at'],
                ]);

                if ($restocked) {
                    $ledger->restock(
                        $item->variant,
                        $qty,
                        (int) $item->unit_cost,
                        'order_return',
                        $return->id,
                        $data['returned_at'],
                        $item->order->code
                    );
                }
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors([$e->getMessage()])->withInput();
        }

        return redirect()->route('admin.returns.index')->with('ok', 'Retur tercatat.');
    }

    public function destroy(OrderReturn $orderReturn, InventoryLedger $ledger)
    {
        $orderReturn->load('item.variant');

        try {
            DB::transaction(function () use ($orderReturn, $ledger) {
                if ($orderReturn->restocked) {
                    $ledger->reverseRestock(
                        $orderReturn->item->variant,
                        (int) $orderReturn->quantity,
                        (int) $orderReturn->item->unit_cost,
                        'order_return',
                        $orderReturn->id,
                        now()->toDateString(),
                        'Hapus retur'
                    );
                }

                $orderReturn->delete();
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors([$e->getMessage()]);
        }

        return redirect()->route('admin.returns.index')->with('ok', 'Catatan retur dihapus.');
    }
}
