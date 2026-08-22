@extends('admin.layout')
@section('title', 'Stok masuk')
@section('content')
<h1>Tambah stok</h1>
<p class="sub">Restock per SKU. Stok bertambah otomatis.</p>

<div class="cards">
  <div class="card">Masuk bulan ini<b>{{ $qty }}</b></div>
</div>

<form method="get" class="form" style="margin-bottom:18px">
  <div>
    <label>Filter bulan</label>
    <input type="month" name="month" value="{{ $month }}">
  </div>
  <div class="full"><button class="btn gray" type="submit">Lihat catatan</button></div>
</form>

<h2>Catat stok masuk</h2>
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
  <div>
    <label>HPP masuk (opsional)</label>
    <input name="unit_cost" value="{{ old('unit_cost') }}" placeholder="Kosong = HPP SKU saat ini">
    <p class="hint">* Kalau beda dari HPP lama, sistem hitung rata-rata tertimbang.</p>
  </div>
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
    <button class="btn" type="submit">Simpan stok masuk</button>
  </div>
</form>

<h2 style="margin-top:28px">Riwayat stok masuk</h2>
<table class="table">
  <tr>
    <th>Tanggal</th>
    <th>SKU</th>
    <th>Qty</th>
    <th>HPP masuk</th>
    <th>Sumber</th>
    <th>Catatan</th>
    <th></th>
  </tr>
  @forelse($entries as $entry)
  <tr>
    <td>{{ $entry->received_at->format('d/m/Y') }}</td>
    <td>{{ $entry->variant?->product?->name }} · {{ $entry->variant?->label }}</td>
    <td>+{{ $entry->quantity }}</td>
    <td>Rp{{ number_format($entry->unit_cost, 0, ',', '.') }}</td>
    <td>{{ $entry->source ?: '—' }}</td>
    <td>{{ $entry->note ?: '—' }}</td>
    <td>
      <form method="post" action="{{ route('admin.stock-ins.destroy', $entry) }}" onsubmit="return confirm('Hapus catatan ini? Stok akan dikurangi kembali.')">
        @csrf @method('DELETE')
        <button class="btn red" type="submit">Hapus</button>
      </form>
    </td>
  </tr>
  @empty
  <tr><td colspan="7">Belum ada stok masuk di bulan ini.</td></tr>
  @endforelse
</table>
@endsection
