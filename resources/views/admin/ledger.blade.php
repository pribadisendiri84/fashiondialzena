@extends('admin.layout')
@section('title', 'Pembukuan')
@section('content')
@php
  $rp = fn ($n) => 'Rp'.number_format((int) $n, 0, ',', '.');
@endphp

<div class="page-head">
  <div>
    <h1>Pembukuan</h1>
    <p class="sub">Satu baris = satu SKU. Kolom laku mengikuti periode terpilih, sedangkan stok sisa dan nilai stok memakai posisi terkini.</p>
  </div>
  <div class="actions head-actions">
    <a class="btn gray" href="{{ route('admin.ledger.export', request()->query()) }}">@include('admin.partials.icon', ['name' => 'download']) Export CSV</a>
  </div>
</div>

@include('admin.partials.date-range', ['resetUrl' => route('admin.ledger')])

<div class="stat-row">
  <div class="stat">
    <span class="bubble tone-pink">@include('admin.partials.icon', ['name' => 'bag'])</span>
    <div>
      <div class="stat-label">SKU aktif</div>
      <div class="stat-value">{{ $skuCount }}<small>SKU</small></div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-blue">@include('admin.partials.icon', ['name' => 'stack'])</span>
    <div>
      <div class="stat-label">Stok sisa</div>
      <div class="stat-value">{{ $stock }}<small>pcs</small></div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-violet">@include('admin.partials.icon', ['name' => 'money'])</span>
    <div>
      <div class="stat-label">Nilai stok (HPP)</div>
      <div class="stat-value">{{ $rp($stockValue) }}</div>
    </div>
  </div>
</div>

<div class="table-wrap">
<table class="table">
  <tr>
    <th>Produk / SKU</th>
    <th>Varian</th>
    <th>HPP</th>
    <th>Jual</th>
    <th>Laku<br><span class="hint">periode</span></th>
    <th>Sisa</th>
    <th>Nilai stok</th>
    <th></th>
  </tr>
  @forelse($items as $item)
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
    <td>{{ $rp($item->stock * $item->cost_price) }}</td>
    <td>
      <a class="btn gray compact" href="{{ route('admin.variants.movements', $item) }}">Riwayat</a>
    </td>
  </tr>
  @empty
  <tr><td colspan="8" class="hint">Belum ada SKU. Upload produk dulu.</td></tr>
  @endforelse
</table>
</div>
@endsection
