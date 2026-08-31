@extends('admin.layout')
@section('title', $product->exists ? 'Edit produk' : 'Upload produk')
@section('content')
<h1>{{ $product->exists ? 'Edit produk' : 'Upload produk' }}</h1>
    <p class="sub">Produk induk + SKU (warna/ukuran). Harga jual ada di level SKU.@can('view-financials') Harga pokok juga di SKU.@endcan</p>

@unless($storageReady ?? false)
  <div class="alert err">Link folder foto belum ada. Di VPS/lokal jalankan <code>php artisan storage:link</code>, lalu refresh.</div>
@endunless

<form class="form" method="post" enctype="multipart/form-data" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}">
  @csrf
  @if($product->exists) @method('PUT') @endif
  <div class="full">
    <label>Nama produk</label>
    <input name="name" value="{{ old('name', $product->name) }}" required>
  </div>
  <div>
    <label>Kategori</label>
    <select name="category_id" class="js-searchable" data-placeholder="Cari kategori…" required>
      <option value="">Pilih kategori</option>
      @foreach($categories as $category)
        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
      @endforeach
    </select>
  </div>
  <div>
    <label>Rating (opsional)</label>
    <input name="rating" value="{{ old('rating', $product->rating) }}" placeholder="4.9">
  </div>

  @unless($product->exists)
  <div class="full"><h2 style="margin:8px 0 0;font-size:16px">SKU pertama</h2></div>
  <div>
    <label>SKU</label>
    <input name="sku" value="{{ old('sku') }}" placeholder="KAOS-HITAM-M" required>
  </div>
  <div>
    <label>Warna (opsional)</label>
    <input name="color" value="{{ old('color') }}" placeholder="Hitam">
  </div>
  <div>
    <label>Ukuran (opsional)</label>
    <input name="size" value="{{ old('size') }}" placeholder="M">
  </div>
  <div>
    <label>Stok awal</label>
    <input type="number" name="stock" min="0" value="{{ old('stock', 0) }}" required>
  </div>
  @can('view-financials')
  <div>
    <label>Harga pokok / HPP (Rp)</label>
    <input name="cost_price" value="{{ old('cost_price', 0) }}" placeholder="80000" required>
  </div>
  @endcan
  <div>
    <label>Harga jual (Rp)</label>
    <input name="sell_price" value="{{ old('sell_price') }}" placeholder="150000" required>
  </div>
  @endunless

  @foreach([
    ['side' => 'front', 'label' => 'Foto depan'],
    ['side' => 'back', 'label' => 'Foto belakang'],
  ] as $photo)
    @php
      $side = $photo['side'];
      $urlField = 'img_'.$side;
      $currentUrl = old($urlField, $product->{$urlField});
    @endphp
  <div class="full photo-upload">
    <label>{{ $photo['label'] }}</label>
    <div class="preview-wrap {{ $currentUrl ? 'filled' : '' }}" id="photo_{{ $side }}_wrap">
      @if($currentUrl)
        <img class="preview" id="photo_{{ $side }}_preview" src="{{ $currentUrl }}" alt="Preview {{ $side === 'front' ? 'depan' : 'belakang' }}">
      @else
        <img class="preview" id="photo_{{ $side }}_preview" alt="" hidden>
      @endif
      <span class="preview-empty" id="photo_{{ $side }}_empty" @if($currentUrl) hidden @endif>
        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="M4 17l5-4 4 3 3-2 4 3"/></svg>
        Belum ada foto
      </span>
    </div>
    <div class="photo-fields">
      <input class="file-input" id="photo_{{ $side }}" type="file" name="photo_{{ $side }}" accept="image/*,.heic,.heif,.jpg,.jpeg,.png,.webp">
      <input class="file-input" id="photo_{{ $side }}_cam" type="file" accept="image/*" capture="environment">
      <div class="photo-actions">
        <button class="btn gray" type="button" onclick="pickPhoto('photo_{{ $side }}')">Galeri / file</button>
        <button class="btn gray" type="button" onclick="pickPhoto('photo_{{ $side }}_cam')">Kamera</button>
        <button class="btn gray" type="button" id="photo_{{ $side }}_clear" onclick="clearPhoto('photo_{{ $side }}')" @unless($currentUrl) hidden @endunless>Hapus</button>
      </div>
      <p class="hint" id="photo_{{ $side }}_name">Foto dikompres otomatis. Nama file: nama-produk-{{ $side === 'front' ? 'depan' : 'belakang' }}-xxxxxx.jpg</p>
      <label class="photo-url">
        <span>Atau tempel link foto</span>
        <input name="{{ $urlField }}" id="{{ $urlField }}" value="{{ $currentUrl }}" placeholder="/storage/products/… atau https://…">
      </label>
    </div>
  </div>
  @endforeach

  <div class="full check">
    <label><input type="checkbox" name="is_new" value="1" @checked(old('is_new', $product->is_new))> New Arrival</label>
    <label><input type="checkbox" name="is_best_seller" value="1" @checked(old('is_best_seller', $product->is_best_seller))> Best Seller</label>
    <label><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product->is_featured))> Best Product</label>
    <label><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active ?? true))> Tampil di website</label>
  </div>
  <div class="full">
    <button class="btn" type="submit">Simpan</button>
    <a class="btn gray" href="{{ route('admin.products.index') }}">Batal</a>
  </div>
</form>

@if($product->exists)
<h2 style="margin-top:32px">Daftar SKU</h2>
<p class="sub">Stok diubah lewat menu Stok masuk / Penjualan / Retur. Isi warna & ukuran biar dashboard tidak terlihat sama.</p>
@foreach($product->variants as $variant)
<form id="sku-{{ $variant->id }}" method="post" action="{{ route('admin.products.variants.update', [$product, $variant]) }}">
  @csrf @method('PUT')
</form>
@endforeach
<table class="table">
  <tr>
    <th>SKU</th>
    <th>Warna</th>
    <th>Ukuran</th>
    @can('view-financials')
    <th>HPP</th>
    @endcan
    <th>Jual</th>
    <th>Stok</th>
    <th></th>
  </tr>
  @foreach($product->variants as $variant)
  <tr>
    <td><input form="sku-{{ $variant->id }}" name="sku" value="{{ $variant->sku }}" required></td>
    <td><input form="sku-{{ $variant->id }}" name="color" value="{{ $variant->color }}" placeholder="Hitam"></td>
    <td><input form="sku-{{ $variant->id }}" name="size" value="{{ $variant->size }}" placeholder="M"></td>
    @can('view-financials')
    <td><input form="sku-{{ $variant->id }}" name="cost_price" value="{{ $variant->cost_price }}" required></td>
    @endcan
    <td><input form="sku-{{ $variant->id }}" name="sell_price" value="{{ $variant->sell_price }}" required></td>
    <td>{{ $variant->stock }}</td>
    <td>
      <label class="hint"><input form="sku-{{ $variant->id }}" type="checkbox" name="is_active" value="1" @checked($variant->is_active)> Aktif</label>
      <button class="btn gray" form="sku-{{ $variant->id }}" type="submit">Update</button>
      <div class="row-actions" style="margin-top:8px">
        <a class="btn gray compact" href="{{ route('admin.variants.movements', $variant) }}">Riwayat</a>
        <a class="btn gray compact" href="{{ route('admin.stock-ins.index', ['variant_id' => $variant->id]) }}">Stok masuk</a>
      </div>
    </td>
  </tr>
  @endforeach
</table>

<h2 style="margin-top:28px">Tambah SKU</h2>
<form class="form" method="post" action="{{ route('admin.products.variants.store', $product) }}">
  @csrf
  <div>
    <label>SKU</label>
    <input name="sku" value="{{ old('sku') }}" placeholder="KAOS-PUTIH-L" required>
  </div>
  <div>
    <label>Warna</label>
    <input name="color" value="{{ old('color') }}" placeholder="Putih">
  </div>
  <div>
    <label>Ukuran</label>
    <input name="size" value="{{ old('size') }}" placeholder="L">
  </div>
  <div>
    <label>Stok awal</label>
    <input type="number" name="stock" min="0" value="{{ old('stock', 0) }}" required>
  </div>
  @can('view-financials')
  <div>
    <label>HPP (Rp)</label>
    <input name="cost_price" value="{{ old('cost_price', 0) }}" required>
  </div>
  @endcan
  <div>
    <label>Harga jual (Rp)</label>
    <input name="sell_price" value="{{ old('sell_price', $product->price) }}" required>
  </div>
  <div class="full">
    <button class="btn" type="submit">Tambah SKU</button>
    <a class="btn gray" href="{{ route('admin.stock-ins.index') }}">+ Stok masuk</a>
  </div>
</form>
@endif
@endsection
