<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

class StorefrontController extends Controller
{
    public function index()
    {
        $products = Product::query()
            ->active()
            ->with(['category', 'variants'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('storefront', [
            'wa' => Setting::getValue('wa_number', '6287777626067'),
            'categories' => Category::query()->orderBy('sort_order')->orderBy('name')->get(),
            'newArrival' => $products->where('is_new', true)->values(),
            'bestSeller' => $products->where('is_best_seller', true)->values(),
            'featured' => $products->where('is_featured', true)->values(),
            'products' => $products,
        ]);
    }
}
