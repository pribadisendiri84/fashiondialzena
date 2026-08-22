@extends('admin.layout')
@section('title', 'Penjualan')
@section('content')
<h1>Penjualan</h1>
<p class="sub">Catat barang laku per SKU. Laba kotor = omzet − refund − (HPP − HPP retur yang kembali ke stok).</p>

<div class="cards">
  <div class="card">Terjual bulan ini<b>{{ $soldQty }}</b></div>
  <div class="card">Retur<b>{{ $returnedQty }}</b></div>
  <div class="card">Omzet<b>Rp{{ number_format($revenue, 0, ',', '.') }}</b></div>
  <div class="card">Refund<b>Rp{{ number_format($refund, 0, ',', '.') }}</b></div>
  <div class="card">HPP<b>Rp{{ number_format($cogs, 0, ',', '.') }}</b></div>
  <div class="card">Laba kotor<b>Rp{{ number_format($gross, 0, ',', '.') }}</b></div>
</div>

<form method="get" class="form" style="margin-bottom:18px">
  <div>
    <label>Filter bulan</label>
    <input type="month" name="month" value="{{ $month }}">
  </div>
  <div class="full"><button class="btn gray" type="submit">Lihat laporan</button></div>
</form>

<h2>Catat penjualan</h2>
<form class="form" method="post" action="{{ route('admin.sales.store') }}">
  @csrf
  <div class="full">
    <label>SKU / Varian</label>
    <select name="product_variant_id" class="js-searchable" data-placeholder="Cari SKU / nama produk…" required>
      <option value="">Pilih SKU</option>
      @foreach($variants as $variant)
        <option value="{{ $variant->id }}" @selected(old('product_variant_id') == $variant->id)>
          {{ $variant->product->name }} · {{ $variant->label }} · stok {{ $variant->stock }} · {{ $variant->sell_price_formatted }}
        </option>
      @endforeach
    </select>
  </div>
  <div>
    <label>Channel</label>
    <select name="channel" required>
      @foreach(['whatsapp' => 'WhatsApp', 'website' => 'Website', 'shopee' => 'Shopee', 'tokopedia' => 'Tokopedia', 'offline' => 'Offline / langsung', 'lainnya' => 'Lainnya'] as $value => $label)
        <option value="{{ $value }}" @selected(old('channel') === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div>
    <label>Jumlah laku</label>
    <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required>
  </div>
  <div>
    <label>Tanggal</label>
    <input type="date" name="sold_at" value="{{ old('sold_at', now()->toDateString()) }}" required>
  </div>
  <div>
    <label>Harga jual (opsional)</label>
    <input name="unit_price" value="{{ old('unit_price') }}" placeholder="Kosong = pakai harga SKU">
  </div>
  <div>
    <label>Nama pembeli (opsional)</label>
    <input name="customer_name" value="{{ old('customer_name') }}">
  </div>
  <div class="full">
    <label>Catatan (opsional)</label>
    <input name="note" value="{{ old('note') }}" placeholder="Transfer / COD">
  </div>
  <div class="full">
    <button class="btn" type="submit">Simpan penjualan</button>
  </div>
</form>

<h2 style="margin-top:28px">Riwayat transaksi</h2>
<table class="table">
  <tr>
    <th>No</th>
    <th>Tanggal</th>
    <th>Channel</th>
    <th>SKU</th>
    <th>Qty</th>
    <th>Jual</th>
    <th>HPP</th>
    <th>Laba</th>
    <th>Pembeli</th>
    <th></th>
  </tr>
  @forelse($orders as $order)
    @foreach($order->items as $item)
    <tr>
      <td>{{ $order->code }}</td>
      <td>{{ $order->sold_at->format('d/m/Y') }}</td>
      <td>{{ ucfirst($order->channel) }}</td>
      <td>
        {{ $item->product->name }}<br>
        <span class="hint">{{ $item->variant?->label }}</span>
      </td>
      <td>{{ $item->quantity }}</td>
      <td>{{ $item->total_formatted }}</td>
      <td>Rp{{ number_format($item->cogs_total, 0, ',', '.') }}</td>
      <td>{{ $order->gross_profit_formatted }}</td>
      <td>{{ $order->customer_name ?: '—' }}</td>
      <td>
        @if($item->returnableQuantity() > 0)
          <a class="btn gray" href="{{ route('admin.returns.index', ['order_item_id' => $item->id]) }}">Retur</a>
        @endif
        @unless($order->items->sum(fn ($i) => $i->returns->count()))
        <form method="post" action="{{ route('admin.sales.destroy', $order) }}" style="display:inline" onsubmit="return confirm('Hapus transaksi ini? Stok akan dikembalikan.')">
          @csrf @method('DELETE')
          <button class="btn red" type="submit">Hapus</button>
        </form>
        @endunless
      </td>
    </tr>
    @endforeach
  @empty
  <tr><td colspan="10">Belum ada penjualan di bulan ini.</td></tr>
  @endforelse
</table>
@endsection
