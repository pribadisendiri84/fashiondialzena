@extends('admin.layout')
@section('title', 'Dashboard')
@section('content')
<h1>Pembukuan</h1>
<p class="sub">Satu baris = satu SKU. Kalau belum isi warna/ukuran, SKU sementara memakai kode dari nama produk.</p>
<div class="cards">
  <div class="card">Total SKU aktif<b>{{ $skuCount }}</b></div>
  <div class="card">Stok sisa<b>{{ $stock }}</b></div>
  <div class="card">Stok menipis (≤3)<b>{{ $low }}</b></div>
  <div class="card">Stok masuk bulan ini<b>{{ $inMonth }}</b></div>
  <div class="card">Terjual bulan ini<b>{{ $soldMonth }}</b></div>
  <div class="card">Omzet bulan ini<b>Rp{{ number_format($revenueMonth, 0, ',', '.') }}</b></div>
  <div class="card">HPP bulan ini<b>Rp{{ number_format($cogsMonth, 0, ',', '.') }}</b></div>
  <div class="card">Laba kotor bulan ini<b>Rp{{ number_format($grossMonth, 0, ',', '.') }}</b></div>
  <div class="card">Retur bulan ini<b>{{ $returnedMonth }}</b></div>
  <div class="card">Refund bulan ini<b>Rp{{ number_format($refundMonth, 0, ',', '.') }}</b></div>
  <div class="card">Total terjual<b>{{ $soldAll }}</b></div>
  <div class="card">Total omzet<b>Rp{{ number_format($revenueAll, 0, ',', '.') }}</b></div>
</div>
<p>
  <a class="btn" href="{{ route('admin.sales.index') }}">+ Catat penjualan</a>
  <a class="btn gray" href="{{ route('admin.returns.index') }}">+ Retur</a>
  <a class="btn gray" href="{{ route('admin.stock-ins.index') }}">+ Tambah stok</a>
  <a class="btn gray" href="{{ route('admin.products.create') }}">Upload produk</a>
</p>
<table class="table">
  <tr>
    <th>Produk / SKU</th>
    <th>Varian</th>
    <th>HPP</th>
    <th>Jual</th>
    <th>Laku</th>
    <th>Sisa</th>
    <th>Nilai stok</th>
  </tr>
  @foreach($items as $item)
  <tr>
    <td>
      {{ $item->product->name }}<br>
      <span class="hint">{{ $item->sku }}</span>
    </td>
    <td>{{ collect([$item->color, $item->size])->filter()->implode(' / ') ?: 'Default' }}</td>
    <td>{{ $item->cost_price_formatted }}</td>
    <td>{{ $item->sell_price_formatted }}</td>
    <td>{{ (int) $item->sold_qty }}</td>
    <td>{{ $item->stock }}</td>
    <td>Rp{{ number_format($item->stock * $item->cost_price, 0, ',', '.') }}</td>
  </tr>
  @endforeach
</table>
@endsection
