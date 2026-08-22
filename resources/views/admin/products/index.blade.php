@extends('admin.layout')
@section('title', 'Produk')
@section('content')
<h1>Produk</h1>
<p class="sub">Produk + SKU (warna/ukuran), harga pokok, harga jual, stok.</p>
<p>
  <a class="btn" href="{{ route('admin.products.create') }}">+ Upload produk</a>
  <a class="btn gray" href="{{ route('admin.stock-ins.index') }}">+ Tambah stok</a>
  <a class="btn gray" href="{{ route('admin.products.index') }}">Semua</a>
  <a class="btn gray" href="{{ route('admin.products.index', ['filter' => 'new']) }}">New Arrival</a>
  <a class="btn gray" href="{{ route('admin.products.index', ['filter' => 'best']) }}">Best Seller</a>
  <a class="btn gray" href="{{ route('admin.products.index', ['filter' => 'featured']) }}">Best Product</a>
  <a class="btn gray" href="{{ route('admin.products.index', ['filter' => 'low']) }}">Stok menipis</a>
</p>
<table class="table">
  <tr><th>Foto</th><th>Nama</th><th>Kategori</th><th>SKU</th><th>Harga</th><th>Laku</th><th>Sisa</th><th>Section</th><th></th></tr>
  @foreach($products as $product)
  <tr>
    <td><img class="thumb" src="{{ $product->img_front }}" alt=""></td>
    <td>{{ $product->name }}</td>
    <td>{{ $product->category->name }}</td>
    <td>{{ $product->variants->count() }}</td>
    <td>{{ $product->price_formatted }}</td>
    <td>{{ (int) $product->sold_qty }}</td>
    <td>{{ $product->stock }}</td>
    <td>
      @if($product->is_new)<span class="badge">New</span>@endif
      @if($product->is_best_seller)<span class="badge">Best Seller</span>@endif
      @if($product->is_featured)<span class="badge">Best Product</span>@endif
      @unless($product->is_active)<span class="badge" style="background:#eee;color:#777">Nonaktif</span>@endunless
    </td>
    <td>
      <a class="btn gray" href="{{ route('admin.products.edit', $product) }}">SKU / Edit</a>
      <form method="post" action="{{ route('admin.products.destroy', $product) }}" style="display:inline" onsubmit="return confirm('Hapus produk ini?')">
        @csrf @method('DELETE')
        <button class="btn red" type="submit">Hapus</button>
      </form>
    </td>
  </tr>
  @endforeach
</table>
@endsection
