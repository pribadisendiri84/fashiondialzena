@extends('admin.layout')
@section('title', $product->exists ? 'Edit produk' : 'Upload produk')
@section('content')
<h1>{{ $product->exists ? 'Edit produk' : 'Upload produk' }}</h1>
<p class="sub">Produk induk + SKU (warna/ukuran). Harga pokok & harga jual ada di level SKU.</p>

@unless($cloudinaryReady ?? false)
  <div class="alert err">Cloudinary belum siap. Isi <code>CLOUDINARY_URL</code> di `.env`, atau paste link manual di bawah.</div>
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
  <div>
    <label>Harga pokok / HPP (Rp)</label>
    <input name="cost_price" value="{{ old('cost_price', 0) }}" placeholder="80000" required>
  </div>
  <div>
    <label>Harga jual (Rp)</label>
    <input name="sell_price" value="{{ old('sell_price') }}" placeholder="150000" required>
  </div>
  @endunless

  <div class="full photo-upload">
    <label>Foto depan</label>
    @if($product->img_front)
      <img class="preview" src="{{ $product->img_front }}" alt="Foto depan">
    @endif
    <input class="file-input" id="photo_front" type="file" name="photo_front" accept="image/*,.heic,.heif,.jpg,.jpeg,.png,.webp" {{ $product->exists ? '' : ($cloudinaryReady ? 'required' : '') }}>
    <input class="file-input" id="photo_front_cam" type="file" accept="image/*" capture="environment">
    <div class="photo-actions">
      <button class="btn gray" type="button" onclick="pickPhoto('photo_front')">Galeri / file</button>
      <button class="btn gray" type="button" onclick="pickPhoto('photo_front_cam')">Kamera</button>
    </div>
    <p class="hint file-name" id="photo_front_name">* Foto dikompres otomatis saat dipilih. Lalu Simpan.</p>
    <input name="img_front" value="{{ old('img_front', $product->exists ? $product->img_front : '') }}" placeholder="https://... (opsional jika sudah upload file)">
  </div>

  <div class="full photo-upload">
    <label>Foto belakang</label>
    @if($product->img_back)
      <img class="preview" src="{{ $product->img_back }}" alt="Foto belakang">
    @endif
    <input class="file-input" id="photo_back" type="file" name="photo_back" accept="image/*,.heic,.heif,.jpg,.jpeg,.png,.webp" {{ $product->exists ? '' : ($cloudinaryReady ? 'required' : '') }}>
    <input class="file-input" id="photo_back_cam" type="file" accept="image/*" capture="environment">
    <div class="photo-actions">
      <button class="btn gray" type="button" onclick="pickPhoto('photo_back')">Galeri / file</button>
      <button class="btn gray" type="button" onclick="pickPhoto('photo_back_cam')">Kamera</button>
    </div>
    <p class="hint file-name" id="photo_back_name">* Foto dikompres otomatis saat dipilih. Lalu Simpan.</p>
    <input name="img_back" value="{{ old('img_back', $product->exists ? $product->img_back : '') }}" placeholder="https://... (opsional jika sudah upload file)">
  </div>

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
    <th>HPP</th>
    <th>Jual</th>
    <th>Stok</th>
    <th></th>
  </tr>
  @foreach($product->variants as $variant)
  <tr>
    <td><input form="sku-{{ $variant->id }}" name="sku" value="{{ $variant->sku }}" required></td>
    <td><input form="sku-{{ $variant->id }}" name="color" value="{{ $variant->color }}" placeholder="Hitam"></td>
    <td><input form="sku-{{ $variant->id }}" name="size" value="{{ $variant->size }}" placeholder="M"></td>
    <td><input form="sku-{{ $variant->id }}" name="cost_price" value="{{ $variant->cost_price }}" required></td>
    <td><input form="sku-{{ $variant->id }}" name="sell_price" value="{{ $variant->sell_price }}" required></td>
    <td>{{ $variant->stock }}</td>
    <td>
      <label class="hint"><input form="sku-{{ $variant->id }}" type="checkbox" name="is_active" value="1" @checked($variant->is_active)> Aktif</label>
      <button class="btn gray" form="sku-{{ $variant->id }}" type="submit">Update</button>
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
  <div>
    <label>HPP (Rp)</label>
    <input name="cost_price" value="{{ old('cost_price', 0) }}" required>
  </div>
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
