<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Ability;
use App\Http\Controllers\Concerns\ResolvesFinancialInput;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    use ResolvesFinancialInput;

    public function store(Request $request, Product $product, InventoryLedger $ledger)
    {
        $data = $this->validated($request, $product);

        DB::transaction(function () use ($product, $data, $ledger, $request) {
            $initialStock = (int) $data['stock'];
            $data['stock'] = 0;
            $data['created_by'] = $request->user()->id;
            $variant = $product->variants()->create($data);

            if ($initialStock > 0) {
                $entry = $variant->stockIns()->create([
                    'quantity' => $initialStock,
                    'unit_cost' => $variant->cost_price,
                    'created_by' => $request->user()->id,
                    'source' => 'Stok awal SKU',
                    'note' => 'Dicatat saat tambah SKU',
                    'received_at' => now()->toDateString(),
                ]);

                $ledger->receive(
                    $variant,
                    $initialStock,
                    (int) $variant->cost_price,
                    'stock_in',
                    $entry->id,
                    now()->toDateString(),
                    'Stok awal SKU'
                );
            }
        });

        return redirect()->route('admin.products.edit', $product)->with('ok', 'SKU ditambahkan.');
    }

    public function update(Request $request, Product $product, ProductVariant $variant)
    {
        abort_unless($variant->product_id === $product->id, 404);

        $data = $this->validated($request, $product, $variant);
        unset($data['stock']);

        $variant->update($data);

        return redirect()->route('admin.products.edit', $product)->with('ok', 'SKU diperbarui. Stok diubah lewat Stok masuk / Penjualan / Retur.');
    }

    public function destroy(Product $product, ProductVariant $variant)
    {
        $this->authorize(Ability::DeleteRecords->value);

        abort_unless($variant->product_id === $product->id, 404);

        if ($variant->hasHistory()) {
            return back()->withErrors(['SKU sudah punya riwayat. Nonaktifkan saja.']);
        }

        if ($product->variants()->count() <= 1) {
            return back()->withErrors(['Minimal 1 SKU per produk.']);
        }

        $variant->delete();

        return redirect()->route('admin.products.edit', $product)->with('ok', 'SKU dihapus.');
    }

    private function validated(Request $request, Product $product, ?ProductVariant $variant = null): array
    {
        $color = trim((string) $request->input('color', ''));
        $size = trim((string) $request->input('size', ''));

        $data = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:80',
                Rule::unique('product_variants', 'sku')->ignore($variant?->id),
            ],
            'color' => ['nullable', 'string', 'max:80'],
            'size' => ['nullable', 'string', 'max:40'],
            'stock' => [$variant ? 'nullable' : 'required', 'integer', 'min:0'],
            'cost_price' => [auth()->user()?->can(Ability::ViewFinancials->value) ? 'required' : 'nullable'],
            'sell_price' => ['required'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $duplicate = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('color', $color)
            ->where('size', $size)
            ->when($variant, fn ($q) => $q->where('id', '!=', $variant->id))
            ->exists();

        if ($duplicate) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'size' => 'Kombinasi warna/ukuran ini sudah ada untuk produk ini.',
            ]);
        }

        $data['cost_price'] = $this->costFromRequest($request, (int) ($variant?->cost_price ?? 0));
        $data['sell_price'] = (int) preg_replace('/\D+/', '', (string) $data['sell_price']);
        $data['sku'] = strtoupper(trim($data['sku']));
        $data['color'] = $color;
        $data['size'] = $size;
        $data['is_active'] = $request->boolean('is_active', true);

        if (! $variant) {
            $data['stock'] = (int) ($data['stock'] ?? 0);
        } else {
            unset($data['stock']);
        }

        return $data;
    }
}
