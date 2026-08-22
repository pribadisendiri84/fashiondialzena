@extends('admin.layout')
@section('title', 'Retur')
@section('content')
<h1>Retur barang</h1>
<p class="sub">Catat barang kembali. Jika dikembalikan ke stok, HPP penjualan ikut dibalik.</p>

<div class="cards">
  <div class="card">Qty retur bulan ini<b>{{ $qty }}</b></div>
  <div class="card">Refund bulan ini<b>Rp{{ number_format($refund, 0, ',', '.') }}</b></div>
</div>

<form method="get" class="form" style="margin-bottom:18px">
  <div>
    <label>Filter bulan</label>
    <input type="month" name="month" value="{{ $month }}">
  </div>
  <div class="full"><button class="btn gray" type="submit">Lihat catatan</button></div>
</form>

<h2>Catat retur</h2>
<form class="form" method="post" action="{{ route('admin.returns.store') }}">
  @csrf
  <div class="full">
    <label>Penjualan</label>
    <select name="order_item_id" class="js-searchable" data-placeholder="Cari no order / produk / SKU…" required>
      <option value="">Pilih transaksi</option>
      @foreach($items as $item)
        <option value="{{ $item->id }}" @selected(old('order_item_id', request('order_item_id')) == $item->id)>
          {{ $item->order->code }} · {{ $item->product->name }} · {{ $item->variant?->label }} · sisa bisa retur {{ $item->returnableQuantity() }}
        </option>
      @endforeach
    </select>
  </div>
  <div>
    <label>Qty retur</label>
    <input type="number" name="quantity" min="1" value="{{ old('quantity', 1) }}" required>
  </div>
  <div>
    <label>Tanggal retur</label>
    <input type="date" name="returned_at" value="{{ old('returned_at', now()->toDateString()) }}" required>
  </div>
  <div>
    <label>Kondisi</label>
    <select name="condition">
      <option value="baik" @selected(old('condition') === 'baik')>Baik / bisa dijual lagi</option>
      <option value="rusak" @selected(old('condition') === 'rusak')>Rusak</option>
      <option value="cacat" @selected(old('condition') === 'cacat')>Cacat</option>
    </select>
  </div>
  <div>
    <label>Refund (Rp)</label>
    <input name="refund_amount" value="{{ old('refund_amount') }}" placeholder="Kosong = qty × harga jual">
  </div>
  <div class="full">
    <label>Alasan</label>
    <input name="reason" value="{{ old('reason') }}" placeholder="Salah ukuran / cacat / dll">
  </div>
  <div class="full check">
    <label><input type="checkbox" name="restocked" value="1" @checked(old('restocked', true))> Kembalikan ke stok</label>
  </div>
  <div class="full">
    <button class="btn" type="submit">Simpan retur</button>
  </div>
</form>

<h2 style="margin-top:28px">Riwayat retur</h2>
<table class="table">
  <tr>
    <th>Tanggal</th>
    <th>No jual</th>
    <th>SKU</th>
    <th>Qty</th>
    <th>Refund</th>
    <th>Stok+</th>
    <th>Alasan</th>
    <th></th>
  </tr>
  @forelse($returns as $return)
  <tr>
    <td>{{ $return->returned_at->format('d/m/Y') }}</td>
    <td>{{ $return->item->order->code }}</td>
    <td>{{ $return->item->product->name }} · {{ $return->item->variant?->label }}</td>
    <td>{{ $return->quantity }}</td>
    <td>{{ $return->refund_amount_formatted }}</td>
    <td>{{ $return->restocked ? 'Ya' : 'Tidak' }}</td>
    <td>{{ $return->reason ?: '—' }}</td>
    <td>
      <form method="post" action="{{ route('admin.returns.destroy', $return) }}" onsubmit="return confirm('Hapus catatan retur ini?')">
        @csrf @method('DELETE')
        <button class="btn red" type="submit">Hapus</button>
      </form>
    </td>
  </tr>
  @empty
  <tr><td colspan="8">Belum ada retur di bulan ini.</td></tr>
  @endforelse
</table>
@endsection
