@extends('admin.layout')
@section('title', 'Stok masuk')
@section('content')
<div class="page-head">
  <div>
    <h1>Tambah stok</h1>
    <p class="sub">Restock per SKU. Stok bertambah otomatis setelah dicatat.</p>
  </div>
</div>

@include('admin.partials.date-range', ['resetUrl' => route('admin.stock-ins.index')])

<div class="stat-row">
  <div class="stat">
    <span class="bubble tone-pink">@include('admin.partials.icon', ['name' => 'inbox'])</span>
    <div><div class="stat-label">Stok masuk</div><div class="stat-value">{{ $qty }}<small>pcs</small></div></div>
  </div>
  <div class="stat">
    <span class="bubble tone-blue">@include('admin.partials.icon', ['name' => 'stack'])</span>
    <div><div class="stat-label">Aktivitas restock</div><div class="stat-value">{{ $entryCount }}<small>transaksi</small></div></div>
  </div>
  @can('view-financials')
  <div class="stat">
    <span class="bubble tone-green">@include('admin.partials.icon', ['name' => 'money'])</span>
    <div><div class="stat-label">Nilai stok masuk</div><div class="stat-value money-value">Rp{{ number_format($stockValue, 0, ',', '.') }}</div></div>
  </div>
  @endcan
</div>

<div class="panel form-panel">
<div class="panel-head">@include('admin.partials.icon', ['name' => 'inbox']) Catat stok masuk</div>
<form class="form" method="post" action="{{ route('admin.stock-ins.store') }}">
  @csrf
  <div class="full">
    <label>SKU</label>
    <select name="product_variant_id" class="js-searchable" data-placeholder="Cari SKU / nama produk…" required>
      <option value="">Pilih SKU</option>
      @foreach($variants as $variant)
        <option value="{{ $variant->id }}" @selected(old('product_variant_id', $selectedVariantId) == $variant->id)>
          {{ $variant->product->name }} · {{ $variant->label }} — sisa {{ $variant->stock }}
        </option>
      @endforeach
    </select>
  </div>
  <div>
    <label>Jumlah masuk</label>
    <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required>
  </div>
  @can('view-financials')
  <div>
    <label>HPP masuk (opsional)</label>
    <input name="unit_cost" value="{{ old('unit_cost') }}" placeholder="Kosong = HPP SKU saat ini">
    <p class="hint">* Kalau beda dari HPP lama, sistem hitung rata-rata tertimbang.</p>
  </div>
  @endcan
  <div>
    <label>Tanggal masuk</label>
    <input type="date" name="received_at" value="{{ old('received_at', now()->toDateString()) }}" required>
  </div>
  <div>
    <label>Sumber (opsional)</label>
    <input name="source" value="{{ old('source') }}" placeholder="Supplier / gudang / restock">
  </div>
  <div class="full">
    <label>Catatan (opsional)</label>
    <input name="note" value="{{ old('note') }}" placeholder="No. invoice">
  </div>
  <div class="full">
    <div class="form-actions">
      <button class="btn gray" type="reset">Bersihkan</button>
      <button class="btn" type="submit">@include('admin.partials.icon', ['name' => 'download']) Simpan stok masuk</button>
    </div>
  </div>
</form>
</div>

<div class="panel table-panel">
<div class="panel-head table-toolbar">
  <span class="panel-title">@include('admin.partials.icon', ['name' => 'chart']) Riwayat stok masuk</span>
  <span class="toolbar-note">{{ $entryCount }} transaksi · {{ $periodLabel }}</span>
</div>
<div class="table-wrap">
<table class="table table-flat">
  <tr>
    <th>Tanggal</th>
    <th>SKU</th>
    <th>Qty</th>
    @can('view-financials')
    <th>HPP masuk</th>
    @endcan
    <th>Sumber</th>
    <th>Catatan</th>
    <th>Dicatat</th>
    <th></th>
  </tr>
  @forelse($entries as $entry)
  <tr>
    <td>{{ $entry->received_at->format('d/m/Y') }}</td>
    <td>{{ $entry->variant?->product?->name }} · {{ $entry->variant?->label }}</td>
    <td>+{{ $entry->quantity }}</td>
    @can('view-financials')
    <td>Rp{{ number_format($entry->unit_cost, 0, ',', '.') }}</td>
    @endcan
    <td>{{ $entry->source ?: '—' }}</td>
    <td>{{ $entry->note ?: '—' }}</td>
    <td>{{ $entry->creator?->name ?: '—' }}</td>
    <td>
      @can('delete-records')
      <form method="post" action="{{ route('admin.stock-ins.destroy', $entry) }}" onsubmit="return confirm('Hapus catatan ini? Stok akan dikurangi kembali.')">
        @csrf @method('DELETE')
        <button class="btn red" type="submit">Hapus</button>
      </form>
      @endcan
    </td>
  </tr>
  @empty
  <tr><td colspan="{{ auth()->user()->can('view-financials') ? 8 : 7 }}">Belum ada stok masuk di periode ini.</td></tr>
  @endforelse
</table>
</div>
</div>
@endsection
