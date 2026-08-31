@extends('admin.layout')
@section('title', 'Retur')
@section('content')
<div class="page-head">
  <div>
    <h1>Retur barang</h1>
    <p class="sub">
      Catat barang kembali.
      @can('view-financials') Jika dikembalikan ke stok, HPP penjualan ikut dibalik. @endcan
    </p>
  </div>
</div>

@include('admin.partials.date-range', ['resetUrl' => route('admin.returns.index')])

<div class="stat-row">
  <div class="stat">
    <span class="bubble tone-amber">@include('admin.partials.icon', ['name' => 'undo'])</span>
    <div><div class="stat-label">Barang diretur</div><div class="stat-value">{{ $qty }}<small>pcs · {{ $returnCount }} transaksi</small></div></div>
  </div>
  <div class="stat">
    <span class="bubble tone-rose">@include('admin.partials.icon', ['name' => 'wallet'])</span>
    <div><div class="stat-label">Total refund</div><div class="stat-value money-value">Rp{{ number_format($refund, 0, ',', '.') }}</div></div>
  </div>
  <div class="stat">
    <span class="bubble tone-green">@include('admin.partials.icon', ['name' => 'stack'])</span>
    <div><div class="stat-label">Kembali ke stok</div><div class="stat-value">{{ $restockedQty }}<small>pcs</small></div></div>
  </div>
</div>

<div class="panel form-panel">
<div class="panel-head">@include('admin.partials.icon', ['name' => 'undo']) Catat retur &amp; refund</div>
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
    <div class="form-actions">
      <button class="btn gray" type="reset">Bersihkan</button>
      <button class="btn" type="submit">@include('admin.partials.icon', ['name' => 'undo']) Simpan retur</button>
    </div>
  </div>
</form>
</div>

<div class="panel table-panel">
<div class="panel-head table-toolbar">
  <span class="panel-title">@include('admin.partials.icon', ['name' => 'chart']) Riwayat retur &amp; refund</span>
  <span class="toolbar-note">{{ $returnCount }} transaksi · {{ $periodLabel }}</span>
</div>
<div class="table-wrap">
<table class="table table-flat">
  <tr>
    <th>Tanggal</th>
    <th>No jual</th>
    <th>SKU</th>
    <th>Qty</th>
    <th>Refund</th>
    <th>Stok+</th>
    <th>Alasan</th>
    <th>Dicatat</th>
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
    <td>{{ $return->creator?->name ?: '—' }}</td>
    <td>
      @can('delete-records')
      <form method="post" action="{{ route('admin.returns.destroy', $return) }}" onsubmit="return confirm('Hapus catatan retur ini?')">
        @csrf @method('DELETE')
        <button class="btn red" type="submit">Hapus</button>
      </form>
      @endcan
    </td>
  </tr>
  @empty
  <tr><td colspan="9">Belum ada retur di periode ini.</td></tr>
  @endforelse
</table>
</div>
</div>
@endsection
