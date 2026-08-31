<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Ability;
use App\Http\Controllers\Controller;
use App\Models\ProductVariant;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class StockMovementController extends Controller
{
    public function index(Request $request, ProductVariant $variant)
    {
        if (! $this->canViewMovements($request)) {
            throw new AuthorizationException;
        }

        $variant->load('product');

        $movements = $variant->movements()
            ->latest('moved_at')
            ->latest('id')
            ->get();

        return view('admin.variants.movements', [
            'variant' => $variant,
            'movements' => $movements,
        ]);
    }

    private function canViewMovements(Request $request): bool
    {
        $user = $request->user();

        return $user->can(Ability::RecordStock->value)
            || $user->can(Ability::ManageCatalog->value)
            || $user->can(Ability::ViewFinancials->value);
    }
}
