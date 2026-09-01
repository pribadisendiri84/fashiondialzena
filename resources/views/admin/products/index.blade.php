@extends('admin.layout')
@section('title', 'Produk')
@section('content')
<div class="page-head">
  <div>
    <h1>Produk</h1>
    <p class="sub">Kelola katalog, SKU, harga, dan stok produk.</p>
  </div>
  @unless($trashed)
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.stock-ins.index') }}">@include('admin.partials.icon', ['name' => 'inbox']) Tambah stok</a>
    <a class="btn" href="{{ route('admin.products.create') }}">@include('admin.partials.icon', ['name' => 'box']) Upload produk</a>
  </div>
  @endunless
</div>
@include('admin.partials.scope-tabs')

<div class="stat-row stat-row-4">
  <div class="stat">
    <span class="bubble tone-pink">@include('admin.partials.icon', ['name' => 'bag'])</span>
    <div><div class="stat-label">Produk aktif</div><div class="stat-value">{{ $productCount }}<small>produk</small></div></div>
  </div>
  <div class="stat">
    <span class="bubble tone-violet">@include('admin.partials.icon', ['name' => 'box'])</span>
    <div><div class="stat-label">Total SKU aktif</div><div class="stat-value">{{ $skuCount }}<small>SKU</small></div></div>
  </div>
  <div class="stat">
    <span class="bubble tone-blue">@include('admin.partials.icon', ['name' => 'stack'])</span>
    <div><div class="stat-label">Stok tersedia</div><div class="stat-value">{{ $stock }}<small>pcs</small></div></div>
  </div>
  <div class="stat">
    <span class="bubble tone-amber">@include('admin.partials.icon', ['name' => 'alert'])</span>
    <div><div class="stat-label">Stok menipis (≤3)</div><div class="stat-value">{{ $lowCount }}<small>SKU</small></div></div>
  </div>
</div>

<div class="panel table-panel">
  <div class="panel-head table-toolbar">
    <span class="panel-title">@include('admin.partials.icon', ['name' => 'box']) Daftar produk</span>
    <form method="get" class="toolbar-form">
      @if($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
      @if($trashed)<input type="hidden" name="trashed" value="1">@endif
      <input name="q" value="{{ $search }}" placeholder="Cari nama, SKU, kategori…">
      <button class="btn gray" type="submit">Cari</button>
      @if($search !== '')<a class="btn ghost" href="{{ route('admin.products.index', array_filter(['filter' => $filter, 'trashed' => $trashed ? 1 : null])) }}">Reset</a>@endif
    </form>
  </div>
  <div class="filter-tabs">
    @foreach([
      '' => 'Semua',
      'new' => 'New Arrival',
      'best' => 'Best Seller',
      'featured' => 'Best Product',
      'low' => 'Stok menipis',
    ] as $value => $label)
      <a class="{{ ($filter ?? '') === $value ? 'active' : '' }}" href="{{ route('admin.products.index', array_filter(['filter' => $value, 'q' => $search, 'trashed' => $trashed ? 1 : null])) }}">{{ $label }}</a>
    @endforeach
  </div>
  <div class="table-wrap">
  <table class="table table-flat">
  <tr><th>Produk</th><th>Kategori</th><th>SKU</th><th>Harga</th><th>Laku</th><th>Sisa</th><th>Label</th><th>Aksi</th></tr>
  @forelse($products as $product)
  <tr class="{{ $product->trashed() ? 'is-deleted' : '' }}">
    <td>
      <div class="product-cell">
        <img class="thumb" src="{{ $product->img_front }}" alt="">
        <div>
          <b>{{ $product->name }}</b>
          <span>{{ $product->variants->first()?->sku ?: 'Belum ada SKU' }}</span>
          @include('admin.partials.timestamps', ['model' => $product])
        </div>
      </div>
    </td>
    <td>{{ $product->category?->name ?? '—' }}</td>
    <td>{{ $product->variants->count() }} varian</td>
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
      @include('admin.partials.row-actions', [
        'item' => $product,
        'edit' => $product->trashed() ? null : route('admin.products.edit', $product),
        'editLabel' => 'Edit / SKU',
        'destroy' => auth()->user()?->can('delete-records') ? route('admin.products.destroy', $product) : null,
        'restore' => route('admin.products.restore', $product),
        'confirm' => 'Hapus produk ini? Superadmin bisa memulihkan.',
      ])
    </td>
  </tr>
  @empty
  <tr><td colspan="8" class="empty-state">{{ $trashed ? 'Tidak ada produk terhapus.' : 'Produk tidak ditemukan.' }}</td></tr>
  @endforelse
</table>
  </div>
</div>
@endsection
