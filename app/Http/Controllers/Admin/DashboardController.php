<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\ProductVariant;
use App\Models\StockIn;

class DashboardController extends Controller
{
    public function index()
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $ordersMonth = Order::query()->whereBetween('sold_at', [$monthStart, $monthEnd]);
        $itemsMonth = OrderItem::query()->whereHas('order', fn ($q) => $q->whereBetween('sold_at', [$monthStart, $monthEnd]));
        $returnsMonth = OrderReturn::query()->whereBetween('returned_at', [$monthStart, $monthEnd]);

        $soldMonth = (int) (clone $itemsMonth)->sum('quantity');
        $revenueMonth = (int) (clone $ordersMonth)->sum('subtotal');
        $cogsMonth = (int) (clone $ordersMonth)->sum('cogs_total');
        $returnedMonth = (int) (clone $returnsMonth)->sum('quantity');
        $refundMonth = (int) (clone $returnsMonth)->sum('refund_amount');
        $cogsReversedMonth = (int) (clone $returnsMonth)->sum('cogs_reversed');
        $inMonth = (int) StockIn::query()->whereBetween('received_at', [$monthStart, $monthEnd])->sum('quantity');

        $netRevenue = $revenueMonth - $refundMonth;
        $netCogs = $cogsMonth - $cogsReversedMonth;

        $items = ProductVariant::query()
            ->with('product.category')
            ->withSum('orderItems as sold_qty', 'quantity')
            ->orderBy('sku')
            ->get();

        return view('admin.dashboard', [
            'skuCount' => ProductVariant::query()->where('is_active', true)->count(),
            'stock' => (int) ProductVariant::query()->sum('stock'),
            'low' => ProductVariant::query()->where('stock', '<=', 3)->count(),
            'soldMonth' => $soldMonth,
            'revenueMonth' => $revenueMonth,
            'cogsMonth' => $cogsMonth,
            'grossMonth' => $netRevenue - $netCogs,
            'returnedMonth' => $returnedMonth,
            'refundMonth' => $refundMonth,
            'inMonth' => $inMonth,
            'soldAll' => (int) OrderItem::query()->sum('quantity'),
            'revenueAll' => (int) Order::query()->sum('subtotal'),
            'items' => $items,
        ]);
    }
}
