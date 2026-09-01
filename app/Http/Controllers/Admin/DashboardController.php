<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Ability;
use App\Http\Controllers\Concerns\ExportsCsv;
use App\Http\Controllers\Concerns\ResolvesDateRange;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockIn;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    use ExportsCsv;
    use ResolvesDateRange;

    public function index(Request $request)
    {
        $this->authorize(Ability::ViewDashboard->value);

        $range = $this->dateRange($request);

        $ordersPeriod = Order::query()->whereBetween('sold_at', [$range['from'], $range['to']]);
        $itemsPeriod = OrderItem::query()->whereHas('order', fn ($q) => $q->whereBetween('sold_at', [$range['from'], $range['to']]));
        $returnsPeriod = OrderReturn::query()->whereBetween('returned_at', [$range['from'], $range['to']]);

        $soldPeriod = (int) (clone $itemsPeriod)->sum('quantity');
        $revenuePeriod = (int) (clone $ordersPeriod)->sum('subtotal');
        $cogsPeriod = (int) (clone $ordersPeriod)->sum('cogs_total');
        $returnedPeriod = (int) (clone $returnsPeriod)->sum('quantity');
        $refundPeriod = (int) (clone $returnsPeriod)->sum('refund_amount');
        $cogsReversedPeriod = (int) (clone $returnsPeriod)->sum('cogs_reversed');
        $inPeriod = (int) StockIn::query()->whereBetween('received_at', [$range['from'], $range['to']])->sum('quantity');

        $netRevenue = $revenuePeriod - $refundPeriod;
        $netCogs = $cogsPeriod - $cogsReversedPeriod;

        $canSeeTeam = $request->user()->can(Ability::ViewDashboard->value);
        $lowSkus = ProductVariant::query()
            ->with('product')
            ->where('is_active', true)
            ->where('stock', '<=', 3)
            ->orderBy('stock')
            ->orderBy('sku')
            ->get();

        return view('admin.dashboard', array_merge($range, [
            'skuCount' => ProductVariant::query()->where('is_active', true)->count(),
            'stock' => (int) ProductVariant::query()->sum('stock'),
            'low' => $lowSkus->count(),
            'lowSkus' => $lowSkus,
            'soldPeriod' => $soldPeriod,
            'revenuePeriod' => $revenuePeriod,
            'cogsPeriod' => $cogsPeriod,
            'grossPeriod' => $revenuePeriod - $cogsPeriod,
            'netPeriod' => $netRevenue - $netCogs,
            'returnedPeriod' => $returnedPeriod,
            'refundPeriod' => $refundPeriod,
            'inPeriod' => $inPeriod,
            'ordersPeriod' => (clone $ordersPeriod)->count(),
            'soldAll' => (int) OrderItem::query()->sum('quantity'),
            'revenueAll' => (int) Order::query()->sum('subtotal'),
            'salesPerformance' => $canSeeTeam ? $this->salesPerformance($range) : collect(),
            'catalogPerformance' => $canSeeTeam ? $this->catalogPerformance($range) : collect(),
        ]));
    }

    /**
     * @param  array{from: string, to: string}  $range
     * @return Collection<int, array{user_id: int|null, name: string, role: string, orders: int, qty: int, revenue: int}>
     */
    private function salesPerformance(array $range): Collection
    {
        $rows = [];

        $orders = Order::query()
            ->with(['creator', 'items'])
            ->whereBetween('sold_at', [$range['from'], $range['to']])
            ->get();

        foreach ($orders as $order) {
            $key = $this->actorKey($order->created_by);
            $rows[$key] ??= $this->actorRow($order->created_by, $order->creator);
            $rows[$key]['orders']++;
            $rows[$key]['qty'] += (int) $order->items->sum('quantity');
            $rows[$key]['revenue'] += (int) $order->subtotal;
        }

        return collect($rows)->sortByDesc('revenue')->values();
    }

    /**
     * @param  array{from: string, to: string}  $range
     * @return Collection<int, array{user_id: int|null, name: string, role: string, products: int, skus: int}>
     */
    private function catalogPerformance(array $range): Collection
    {
        $from = Carbon::parse($range['from'])->startOfDay();
        $to = Carbon::parse($range['to'])->endOfDay();
        $rows = [];

        foreach (Product::query()->with('creator')->whereBetween('created_at', [$from, $to])->get() as $product) {
            $key = $this->actorKey($product->created_by);
            $rows[$key] ??= $this->actorRow($product->created_by, $product->creator) + ['products' => 0, 'skus' => 0];
            $rows[$key]['products']++;
        }

        foreach (ProductVariant::query()->with('creator')->whereBetween('created_at', [$from, $to])->get() as $variant) {
            $key = $this->actorKey($variant->created_by);
            $rows[$key] ??= $this->actorRow($variant->created_by, $variant->creator) + ['products' => 0, 'skus' => 0];
            $rows[$key]['skus']++;
        }

        return collect($rows)
            ->sortByDesc(fn (array $row) => [$row['skus'], $row['products']])
            ->values();
    }

    private function actorKey(mixed $userId): string
    {
        return $userId === null ? 'none' : (string) $userId;
    }

    /**
     * @return array{user_id: int|null, name: string, role: string, orders: int, qty: int, revenue: int, products: int, skus: int}
     */
    private function actorRow(mixed $userId, mixed $creator): array
    {
        return [
            'user_id' => $userId,
            'name' => $creator?->name ?: 'Tidak tercatat',
            'role' => $creator?->resolvedRole()->label() ?: '—',
            'orders' => 0,
            'qty' => 0,
            'revenue' => 0,
            'products' => 0,
            'skus' => 0,
        ];
    }

    public function ledger(Request $request)
    {
        $this->authorize(Ability::ViewFinancials->value);

        $range = $this->dateRange($request);

        $items = ProductVariant::query()
            ->with('product.category')
            ->withSum(['orderItems as sold_qty' => function ($query) use ($range) {
                $query->whereHas('order', fn ($q) => $q->whereBetween('sold_at', [$range['from'], $range['to']]));
            }], 'quantity')
            ->orderBy('sku')
            ->get();

        return view('admin.ledger', array_merge($range, [
            'items' => $items,
            'skuCount' => $items->where('is_active', true)->count(),
            'stock' => (int) $items->sum('stock'),
            'stockValue' => (int) $items->sum(fn ($item) => $item->stock * $item->cost_price),
        ]));
    }

    public function exportLedger(Request $request)
    {
        $this->authorize(Ability::ViewFinancials->value);

        $range = $this->dateRange($request);

        $items = ProductVariant::query()
            ->with('product.category')
            ->withSum(['orderItems as sold_qty' => function ($query) use ($range) {
                $query->whereHas('order', fn ($q) => $q->whereBetween('sold_at', [$range['from'], $range['to']]));
            }], 'quantity')
            ->orderBy('sku')
            ->get();

        $rows = $items->map(fn (ProductVariant $item) => [
            $item->product->name,
            $item->sku,
            collect([$item->color, $item->size])->filter()->implode(' / ') ?: 'Default',
            $item->product->category?->name ?: '',
            (int) $item->cost_price,
            (int) $item->sell_price,
            (int) $item->sold_qty,
            (int) $item->stock,
            (int) ($item->stock * $item->cost_price),
        ]);

        return $this->csvDownload(
            'pembukuan-'.$range['from'].'-'.$range['to'].'.csv',
            ['Produk', 'SKU', 'Varian', 'Kategori', 'HPP', 'Jual', 'Laku periode', 'Sisa', 'Nilai stok'],
            $rows
        );
    }
}
