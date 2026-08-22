<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CloudinaryUploader;
use App\Services\InventoryLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['category', 'variants'])
            ->withSum('orderItems as sold_qty', 'quantity')
            ->latest();

        $query->when($request->filter === 'new', fn ($q) => $q->where('is_new', true));
        $query->when($request->filter === 'best', fn ($q) => $q->where('is_best_seller', true));
        $query->when($request->filter === 'featured', fn ($q) => $q->where('is_featured', true));
        $query->when($request->filter === 'low', fn ($q) => $q->whereHas('variants', fn ($v) => $v->where('stock', '<=', 3)));

        return view('admin.products.index', [
            'products' => $query->get(),
            'filter' => $request->filter,
        ]);
    }

    public function create(CloudinaryUploader $uploader)
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true]),
            'categories' => Category::query()->orderBy('name')->get(),
            'cloudinaryReady' => $uploader->configured(),
        ]);
    }

    public function store(Request $request, CloudinaryUploader $uploader, InventoryLedger $ledger)
    {
        $data = $this->validatedProduct($request, true, $uploader);
        $variant = $this->validatedVariant($request);

        $product = DB::transaction(function () use ($data, $variant, $ledger) {
            $product = Product::query()->create($data);
            $initialStock = (int) $variant['stock'];
            $variant['stock'] = 0;
            $created = $product->variants()->create($variant);

            if ($initialStock > 0) {
                $entry = $created->stockIns()->create([
                    'quantity' => $initialStock,
                    'unit_cost' => $created->cost_price,
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

    public function edit(Product $product, CloudinaryUploader $uploader)
    {
        $product->load('variants');

        return view('admin.products.form', [
            'product' => $product,
            'categories' => Category::query()->orderBy('name')->get(),
            'cloudinaryReady' => $uploader->configured(),
        ]);
    }

    public function update(Request $request, Product $product, CloudinaryUploader $uploader)
    {
        $product->update($this->validatedProduct($request, false, $uploader, $product));

        return redirect()->route('admin.products.edit', $product)->with('ok', 'Produk diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists() || $product->stockIns()->exists()) {
            return back()->withErrors(['Produk sudah punya riwayat stok/penjualan. Nonaktifkan saja jika tidak ingin ditampilkan.']);
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('ok', 'Produk dihapus.');
    }

    private function validatedProduct(Request $request, bool $creating, CloudinaryUploader $uploader, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'category_id' => ['required', 'exists:categories,id'],
            'photo_front' => ['nullable', 'file', 'max:12288', 'mimes:jpg,jpeg,png,webp,heic,heif,gif'],
            'photo_back' => ['nullable', 'file', 'max:12288', 'mimes:jpg,jpeg,png,webp,heic,heif,gif'],
            'img_front' => ['nullable', 'url', 'max:500'],
            'img_back' => ['nullable', 'url', 'max:500'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
        ]);

        $data['is_new'] = $request->boolean('is_new');
        $data['is_best_seller'] = $request->boolean('is_best_seller');
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_active'] = $request->boolean('is_active');

        try {
            $data['img_front'] = $this->resolveImageUrl($request, $uploader, 'photo_front', 'img_front', $product?->img_front, $creating, 'depan');
            $data['img_back'] = $this->resolveImageUrl($request, $uploader, 'photo_back', 'img_back', $product?->img_back, $creating, 'belakang');
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
            'cost_price' => ['required'],
            'sell_price' => ['required'],
        ]);

        $data['cost_price'] = (int) preg_replace('/\D+/', '', (string) $data['cost_price']);
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
        CloudinaryUploader $uploader,
        string $fileField,
        string $urlField,
        ?string $existing,
        bool $creating,
        string $label
    ): string {
        if ($request->hasFile($fileField)) {
            return $uploader->upload($request->file($fileField));
        }

        $url = trim((string) $request->input($urlField, ''));
        if ($url !== '') {
            return $url;
        }

        if (! $creating && filled($existing)) {
            return $existing;
        }

        throw ValidationException::withMessages([
            $fileField => 'Foto '.$label.' wajib: upload file atau isi link URL.',
        ]);
    }
}
