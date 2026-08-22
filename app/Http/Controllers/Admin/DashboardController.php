<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ResolvesDateRange;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\ProductVariant;
use App\Models\StockIn;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    use ResolvesDateRange;

    public function index(Request $request)
    {
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

        return view('admin.dashboard', array_merge($range, [
            'skuCount' => ProductVariant::query()->where('is_active', true)->count(),
            'stock' => (int) ProductVariant::query()->sum('stock'),
            'low' => ProductVariant::query()->where('stock', '<=', 3)->count(),
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
        ]));
    }

    public function ledger(Request $request)
    {
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
}
