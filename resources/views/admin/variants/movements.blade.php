@extends('admin.layout')
@section('title', 'Riwayat stok '.$variant->sku)
@section('content')
@php
  $rp = fn ($n) => 'Rp'.number_format((int) $n, 0, ',', '.');
@endphp

<div class="page-head">
  <div>
    <h1>Riwayat stok</h1>
    <p class="sub">
      {{ $variant->product->name }} · {{ $variant->label }} · sisa {{ $variant->stock }}
    </p>
  </div>
  <div class="actions head-actions">
    @can('record-stock')
    <a class="btn gray" href="{{ route('admin.stock-ins.index', ['variant_id' => $variant->id]) }}">@include('admin.partials.icon', ['name' => 'inbox']) Stok masuk</a>
    @endcan
    @can('view-financials')
    <a class="btn gray" href="{{ route('admin.ledger') }}">Kembali ke pembukuan</a>
    @else
    <a class="btn gray" href="{{ route('admin.products.edit', $variant->product) }}">Kembali ke produk</a>
    @endcan
  </div>
</div>

<div class="stat-row">
  <div class="stat">
    <span class="bubble tone-pink">@include('admin.partials.icon', ['name' => 'bag'])</span>
    <div>
      <div class="stat-label">SKU</div>
      <div class="stat-value" style="font-size:20px">{{ $variant->sku }}</div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-blue">@include('admin.partials.icon', ['name' => 'stack'])</span>
    <div>
      <div class="stat-label">Stok sisa</div>
      <div class="stat-value">{{ $variant->stock }}<small>pcs</small></div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-amber">@include('admin.partials.icon', ['name' => 'chart'])</span>
    <div>
      <div class="stat-label">Pergerakan</div>
      <div class="stat-value">{{ $movements->count() }}<small>baris</small></div>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'inbox']) Jejak masuk / jual / retur</div>
  <div class="table-wrap">
    <table class="table table-flat">
      <tr>
        <th>Tanggal</th>
        <th>Jenis</th>
        <th>Qty</th>
        <th>Stok setelah</th>
        @can('view-financials')
        <th>HPP</th>
        @endcan
        <th>Catatan</th>
      </tr>
      @forelse($movements as $movement)
      <tr>
        <td>{{ $movement->moved_at?->translatedFormat('d M Y') }}</td>
        <td>{{ $movement->typeLabel() }}</td>
        <td class="{{ $movement->quantity < 0 ? 'qty-out' : 'qty-in' }}">
          {{ $movement->quantity > 0 ? '+'.$movement->quantity : $movement->quantity }}
        </td>
        <td>{{ $movement->stock_after }}</td>
        @can('view-financials')
        <td>{{ $rp($movement->unit_cost) }}</td>
        @endcan
        <td>{{ $movement->note ?: '—' }}</td>
      </tr>
      @empty
      <tr><td colspan="{{ auth()->user()->can('view-financials') ? 6 : 5 }}" class="hint">Belum ada pergerakan stok untuk SKU ini.</td></tr>
      @endforelse
    </table>
  </div>
</div>
@endsection
