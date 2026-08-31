<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Ability;
use App\Http\Controllers\Concerns\ResolvesFinancialInput;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryLedger;
use App\Services\ProductImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ProductController extends Controller
{
    use ResolvesFinancialInput;

    public function index(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $query = Product::query()
            ->with(['category', 'variants'])
            ->withSum('orderItems as sold_qty', 'quantity')
            ->latest();

        $query->when($search !== '', function ($q) use ($search) {
            $q->where(function ($match) use ($search) {
                $match->where('name', 'like', '%'.$search.'%')
                    ->orWhereHas('category', fn ($category) => $category->where('name', 'like', '%'.$search.'%'))
                    ->orWhereHas('variants', fn ($variant) => $variant->where('sku', 'like', '%'.$search.'%'));
            });
        });
        $query->when($request->filter === 'new', fn ($q) => $q->where('is_new', true));
        $query->when($request->filter === 'best', fn ($q) => $q->where('is_best_seller', true));
        $query->when($request->filter === 'featured', fn ($q) => $q->where('is_featured', true));
        $query->when($request->filter === 'low', fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('stock', '<=', 3)));

        return view('admin.products.index', [
            'products' => $query->get(),
            'filter' => $request->filter,
            'search' => $search,
            'productCount' => Product::query()->where('is_active', true)->count(),
            'skuCount' => ProductVariant::query()->where('is_active', true)->count(),
            'stock' => (int) ProductVariant::query()->sum('stock'),
            'lowCount' => ProductVariant::query()->where('is_active', true)->where('stock', '<=', 3)->count(),
        ]);
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true]),
            'categories' => Category::query()->orderBy('name')->get(),
            'storageReady' => ProductImageStore::publicLinkReady(),
        ]);
    }

    public function store(Request $request, ProductImageStore $images, InventoryLedger $ledger)
    {
        $data = $this->validatedProduct($request, true, $images);
        $variant = $this->validatedVariant($request);

        $product = DB::transaction(function () use ($data, $variant, $ledger, $request) {
            $data['created_by'] = $request->user()->id;
            $product = Product::query()->create($data);
            $initialStock = (int) $variant['stock'];
            $variant['stock'] = 0;
            $variant['created_by'] = $request->user()->id;
            $created = $product->variants()->create($variant);

            if ($initialStock > 0) {
                $entry = $created->stockIns()->create([
                    'quantity' => $initialStock,
                    'unit_cost' => $created->cost_price,
                    'created_by' => $request->user()->id,
                    'source' => 'Stok awal',
                    'note' => 'Dicatat saat upload produk',
                    'received_at' => now()->toDateString(),
                ]);

                $ledger->receive(
                    $created,
                    $initialStock,
                    (int) $created->cost_price,
                    'stock_in',
                    $entry->id,
                    now()->toDateString(),
                    'Stok awal'
                );
            }

            return $product;
        });

        return redirect()->route('admin.products.edit', $product)->with('ok', 'Produk & SKU tersimpan. Bisa tambah warna/ukuran di bawah.');
    }

    public function edit(Product $product)
    {
        $product->load('variants');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
            'storageReady' => ProductImageStore::publicLinkReady(),
        ]);
    }

    public function update(Request $request, Product $product, ProductImageStore $images)
    {
        $product->update($this->validatedProduct($request, false, $images, $product));

        return redirect()->route('admin.products.edit', $product)->with('ok', 'Produk diperbarui.');
    }

    public function destroy(Product $product, ProductImageStore $images)
    {
        $this->authorize(Ability::DeleteRecords->value);

        if ($product->orderItems()->exists() || $product->stockIns()->exists()) {
            return back()->withErrors(['Produk sudah punya riwayat stok/penjualan. Nonaktifkan saja jika tidak ingin ditampilkan.']);
        }

        $images->deleteIfLocal($product->img_front);
        $images->deleteIfLocal($product->img_back);
        $product->delete();

        return redirect()->route('admin.products.index')->with('ok', 'Produk dihapus.');
    }

    private function validatedProduct(Request $request, bool $creating, ProductImageStore $images, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'category_id' => ['required', 'exists:categories,id'],
            'photo_front' => ['nullable', 'file', 'max:12288', 'mimes:jpg,jpeg,png,webp,heic,heif,gif'],
            'photo_back' => ['nullable', 'file', 'max:12288', 'mimes:jpg,jpeg,png,webp,heic,heif,gif'],
            'img_front' => ['nullable', 'string', 'max:500'],
            'img_back' => ['nullable', 'string', 'max:500'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ]);

        $data['is_new'] = $request->boolean('is_new');
        $data['is_best_seller'] = $request->boolean('is_best_seller');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        try {
            $data['img_front'] = $this->resolveImageUrl($request, $images, 'photo_front', 'img_front', $product?->img_front, $creating, 'depan', $data['name']);
            $data['img_back'] = $this->resolveImageUrl($request, $images, 'photo_back', 'img_back', $product?->img_back, $creating, 'belakang', $data['name']);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['photo_front' => $e->getMessage()]);
        }

        unset($data['photo_front'], $data['photo_back']);

        return $data;
    }

    private function validatedVariant(Request $request): array
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:80', 'unique:product_variants,sku'],
            'color' => ['nullable', 'string', 'max:80'],
            'size' => ['nullable', 'string', 'max:40'],
            'stock' => ['required', 'integer', 'min:0'],
            'cost_price' => [auth()->user()?->can(Ability::ViewFinancials->value) ? 'required' : 'nullable'],
            'sell_price' => ['required'],
        ]);

        $data['cost_price'] = $this->costFromRequest($request, 0);
        $data['sell_price'] = (int) preg_replace('/\D+/', '', (string) $data['sell_price']);
        $data['sku'] = strtoupper(trim($data['sku']));
        $data['color'] = trim((string) ($data['color'] ?? ''));
        $data['size'] = trim((string) ($data['size'] ?? ''));
        $data['is_active'] = true;

        if ($data['sell_price'] < 1) {
            throw ValidationException::withMessages(['sell_price' => 'Harga jual wajib diisi.']);
        }

        return $data;
    }

    private function resolveImageUrl(
        Request $request,
        ProductImageStore $images,
        string $fileField,
        string $urlField,
        ?string $existing,
        bool $creating,
        string $side,
        string $productName
    ): string {
        if ($request->hasFile($fileField)) {
            return $images->store($request->file($fileField), $productName, $side, $existing);
        }

        $url = trim((string) $request->input($urlField, ''));
        if ($url !== '' && $this->isAllowedImageUrl($url)) {
            return $url;
        }

        if (! $creating && filled($existing)) {
            return $existing;
        }

        throw ValidationException::withMessages([
            $fileField => 'Foto '.$side.' wajib: upload file atau isi link.',
        ]);
    }

    private function isAllowedImageUrl(string $url): bool
    {
        if (str_starts_with($url, ProductImageStore::PREFIX)) {
            return true;
        }

        return (bool) filter_var($url, FILTER_VALIDATE_URL);
    }
}
