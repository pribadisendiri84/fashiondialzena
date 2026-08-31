@extends('admin.layout')
@section('title', 'Dashboard')
@section('content')
@php
  $rp = fn ($n) => 'Rp'.number_format((int) $n, 0, ',', '.');
@endphp

<div class="page-head">
  <div>
    <h1>Dashboard</h1>
    <p class="sub">
      Ringkasan stok dan aktivitas toko.
      @can('view-financials') Detail per SKU ada di menu Pembukuan. @endcan
    </p>
  </div>
</div>

@include('admin.partials.date-range', ['resetUrl' => route('admin.dashboard')])

<div class="stat-row">
  <div class="stat">
    <span class="bubble tone-pink">@include('admin.partials.icon', ['name' => 'bag'])</span>
    <div>
      <div class="stat-label">Total SKU aktif</div>
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
    <span class="bubble tone-amber">@include('admin.partials.icon', ['name' => 'alert'])</span>
    <div>
      <div class="stat-label">Stok menipis (≤3)</div>
      <div class="stat-value">{{ $low }}<small>SKU</small></div>
    </div>
  </div>
</div>

<div class="panel">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'alert']) SKU menipis (≤3)</div>
  <div class="table-wrap">
    <table class="table table-flat">
      <tr>
        <th>Produk</th>
        <th>SKU</th>
        <th>Varian</th>
        <th>Sisa</th>
        <th></th>
      </tr>
      @forelse($lowSkus as $sku)
      <tr>
        <td>{{ $sku->product->name }}</td>
        <td>{{ $sku->sku }}</td>
        <td>{{ collect([$sku->color, $sku->size])->filter()->implode(' / ') ?: 'Default' }}</td>
        <td>{{ $sku->stock }}@if($sku->stock === 0) <span class="hint">Habis</span>@endif</td>
        <td>
          <div class="row-actions">
            <a class="btn gray compact" href="{{ route('admin.stock-ins.index', ['variant_id' => $sku->id]) }}">Stok masuk</a>
            <a class="btn gray compact" href="{{ route('admin.variants.movements', $sku) }}">Riwayat</a>
          </div>
        </td>
      </tr>
      @empty
      <tr><td colspan="5" class="hint">Tidak ada SKU aktif yang menipis.</td></tr>
      @endforelse
    </table>
  </div>
</div>

<div class="panel">
  <div class="panel-head">
    @include('admin.partials.icon', ['name' => 'chart'])
    Stok &amp; Aktivitas
  </div>
  <div class="metric-row">
    <div class="metric">
      <span class="bubble tone-green">@include('admin.partials.icon', ['name' => 'download'])</span>
      <div>
        <div class="metric-label">Stok masuk periode ini</div>
        <div class="metric-value">{{ $inPeriod }}<small>pcs</small></div>
      </div>
    </div>
    <div class="metric">
      <span class="bubble tone-pink">@include('admin.partials.icon', ['name' => 'cart'])</span>
      <div>
        <div class="metric-label">Terjual periode ini</div>
        <div class="metric-value">{{ $soldPeriod }}<small>pcs</small></div>
      </div>
    </div>
    <div class="metric">
      <span class="bubble tone-violet">@include('admin.partials.icon', ['name' => 'money'])</span>
      <div>
        <div class="metric-label">Total terjual</div>
        <div class="metric-value">{{ $soldAll }}<small>pcs</small></div>
      </div>
    </div>
    <div class="metric">
      <span class="bubble tone-blue">@include('admin.partials.icon', ['name' => 'trend'])</span>
      <div>
        <div class="metric-label">Total omzet</div>
        <div class="metric-value">{{ $rp($revenueAll) }}</div>
      </div>
    </div>
  </div>
</div>

@can('manage-users')
@if($salesPerformance->isNotEmpty())
<div class="panel">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'cart']) Performa penjualan · {{ $periodLabel }}</div>
  <div class="table-wrap">
    <table class="table table-flat">
      <tr>
        <th>Nama</th>
        <th>Peran</th>
        <th>Transaksi</th>
        <th>Qty jual</th>
        <th>Omzet</th>
      </tr>
      @foreach($salesPerformance as $row)
      <tr>
        <td>
          @if($row['user_id'])
            <a href="{{ route('admin.sales.index', ['from' => $from, 'to' => $to, 'recorded_by' => $row['user_id']]) }}">{{ $row['name'] }}</a>
          @else
            {{ $row['name'] }}
          @endif
        </td>
        <td>{{ $row['role'] }}</td>
        <td>{{ $row['orders'] }}</td>
        <td>{{ $row['qty'] }}</td>
        <td>Rp{{ number_format($row['revenue'], 0, ',', '.') }}</td>
      </tr>
      @endforeach
    </table>
  </div>
</div>
@endif

@if($catalogPerformance->isNotEmpty())
<div class="panel">
  <div class="panel-head">@include('admin.partials.icon', ['name' => 'box']) Performa input katalog · {{ $periodLabel }}</div>
  <div class="table-wrap">
    <table class="table table-flat">
      <tr>
        <th>Nama</th>
        <th>Peran</th>
        <th>Produk baru</th>
        <th>SKU baru</th>
      </tr>
      @foreach($catalogPerformance as $row)
      <tr>
        <td>{{ $row['name'] }}</td>
        <td>{{ $row['role'] }}</td>
        <td>{{ $row['products'] }}</td>
        <td>{{ $row['skus'] }}</td>
      </tr>
      @endforeach
    </table>
  </div>
</div>
@endif
@endcan

@can('view-financials')
<div class="panel">
  <div class="panel-head">
    @include('admin.partials.icon', ['name' => 'wallet'])
    Keuangan
  </div>
  <div class="money-grid">
    <div class="metric money">
      <div>
        <div class="metric-label">Omzet periode ini</div>
        <div class="metric-value">{{ $rp($revenuePeriod) }}</div>
      </div>
      <span class="bubble tone-green">@include('admin.partials.icon', ['name' => 'trend'])</span>
    </div>
    <div class="metric money">
      <div>
        <div class="metric-label">HPP periode ini</div>
        <div class="metric-value">{{ $rp($cogsPeriod) }}</div>
      </div>
      <span class="bubble tone-violet">@include('admin.partials.icon', ['name' => 'tag'])</span>
    </div>
    <div class="metric money">
      <div>
        <div class="metric-label">Laba kotor periode ini</div>
        <div class="metric-value">{{ $rp($grossPeriod) }}</div>
      </div>
      <span class="bubble tone-green">@include('admin.partials.icon', ['name' => 'money'])</span>
    </div>
    <div class="metric money">
      <div>
        <div class="metric-label">Retur periode ini</div>
        <div class="metric-value">{{ $returnedPeriod }}<small>pcs</small></div>
      </div>
      <span class="bubble tone-amber">@include('admin.partials.icon', ['name' => 'undo'])</span>
    </div>
    <div class="metric money">
      <div>
        <div class="metric-label">Refund periode ini</div>
        <div class="metric-value">{{ $rp($refundPeriod) }}</div>
      </div>
      <span class="bubble tone-rose">@include('admin.partials.icon', ['name' => 'wallet'])</span>
    </div>
    <div class="metric money">
      <div>
        <div class="metric-label">Laba bersih periode ini</div>
        <div class="metric-value">{{ $rp($netPeriod) }}</div>
      </div>
      <span class="bubble tone-blue">@include('admin.partials.icon', ['name' => 'chart'])</span>
    </div>
  </div>
</div>

<div class="summary">
  <span class="bubble">@include('admin.partials.icon', ['name' => 'trophy'])</span>
  <div>
    <b>Ringkasan Performa {{ $periodLabel }}</b>
    <p>Omzet {{ $rp($revenuePeriod) }} dengan laba bersih {{ $rp($netPeriod) }} dari {{ $ordersPeriod }} transaksi. Retur {{ $returnedPeriod }} pcs.</p>
  </div>
</div>
@endcan
@endsection
