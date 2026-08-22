@extends('admin.layout')
@section('title', 'Penjualan')
@section('content')
<div class="page-head">
  <div>
    <h1>Penjualan</h1>
    <p class="sub">Catat barang laku per SKU. Laba kotor = omzet − refund − (HPP − HPP retur yang kembali ke stok).</p>
  </div>
</div>

@include('admin.partials.date-range', [
  'resetUrl' => route('admin.sales.index'),
  'keep' => array_filter(['q' => $search]),
])

<div class="stat-row">
  <div class="stat">
    <span class="bubble tone-pink">@include('admin.partials.icon', ['name' => 'cart'])</span>
    <div>
      <div class="stat-label">Terjual</div>
      <div class="stat-value">{{ $soldQty }}<small>pcs</small></div>
      <div class="stat-note">{{ $orderCount }} transaksi</div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-amber">@include('admin.partials.icon', ['name' => 'undo'])</span>
    <div>
      <div class="stat-label">Retur</div>
      <div class="stat-value">{{ $returnedQty }}<small>pcs</small></div>
      <div class="stat-note">Barang kembali</div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-green">@include('admin.partials.icon', ['name' => 'trend'])</span>
    <div>
      <div class="stat-label">Omzet</div>
      <div class="stat-value money-value">Rp{{ number_format($revenue, 0, ',', '.') }}</div>
      <div class="stat-note">Total penjualan</div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-rose">@include('admin.partials.icon', ['name' => 'wallet'])</span>
    <div>
      <div class="stat-label">Refund</div>
      <div class="stat-value money-value">Rp{{ number_format($refund, 0, ',', '.') }}</div>
      <div class="stat-note">Total refund</div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-violet">@include('admin.partials.icon', ['name' => 'tag'])</span>
    <div>
      <div class="stat-label">HPP</div>
      <div class="stat-value money-value">Rp{{ number_format($cogs, 0, ',', '.') }}</div>
      <div class="stat-note">Harga pokok terjual</div>
    </div>
  </div>
  <div class="stat">
    <span class="bubble tone-blue">@include('admin.partials.icon', ['name' => 'chart'])</span>
    <div>
      <div class="stat-label">Laba kotor</div>
      <div class="stat-value money-value">Rp{{ number_format($gross, 0, ',', '.') }}</div>
      <div class="stat-note">Keuntungan kotor</div>
    </div>
  </div>
</div>

<div class="panel form-panel">
<div class="panel-head">@include('admin.partials.icon', ['name' => 'cart']) Catat penjualan</div>
<form class="form sale-form" method="post" action="{{ route('admin.sales.store') }}" id="sale-form">
  @csrf
  <div>
    <label>Channel</label>
    <select name="channel" required>
      @foreach(['whatsapp' => 'WhatsApp', 'website' => 'Website', 'shopee' => 'Shopee', 'tokopedia' => 'Tokopedia', 'offline' => 'Offline / langsung', 'lainnya' => 'Lainnya'] as $value => $label)
        <option value="{{ $value }}" @selected(old('channel') === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </div>
  <div>
    <label>Tanggal</label>
    <input type="date" name="sold_at" value="{{ old('sold_at', now()->toDateString()) }}" required>
  </div>
  <div>
    <label>Nama pembeli (opsional)</label>
    <input name="customer_name" value="{{ old('customer_name') }}" placeholder="Contoh: Andi">
  </div>
  <div>
    <label>Catatan (opsional)</label>
    <input name="note" value="{{ old('note') }}" placeholder="Transfer / COD">
  </div>

  @php
    $saleItems = old('items') ?: [[
      'product_variant_id' => '',
      'quantity' => 1,
      'unit_price' => '',
    ]];
  @endphp
  <div class="full sale-items-section">
    <div class="sale-items-head">
      <div>
        <label>Item penjualan</label>
        <p class="hint">Satu nomor order dapat berisi beberapa SKU.</p>
      </div>
      <button class="btn gray compact" type="button" id="add-sale-item">+ Tambah item</button>
    </div>
    <div class="sale-item-labels" aria-hidden="true">
      <span>SKU / Varian</span><span>Qty</span><span>Harga jual</span><span></span>
    </div>
    <div id="sale-items">
      @foreach($saleItems as $index => $saleItem)
        <div class="sale-item-row">
          <select name="items[{{ $index }}][product_variant_id]" class="js-searchable sale-variant" data-placeholder="Cari SKU / produk…" required>
            <option value="">Pilih SKU</option>
            @foreach($variants as $variant)
              <option
                value="{{ $variant->id }}"
                data-price="{{ $variant->sell_price }}"
                data-stock="{{ $variant->stock }}"
                @selected(($saleItem['product_variant_id'] ?? '') == $variant->id)
              >
                {{ $variant->product->name }} · {{ $variant->label }} · stok {{ $variant->stock }} · {{ $variant->sell_price_formatted }}
              </option>
            @endforeach
          </select>
          <input class="sale-qty" type="number" name="items[{{ $index }}][quantity]" min="1" value="{{ $saleItem['quantity'] ?? 1 }}" aria-label="Jumlah laku" required>
          <input class="sale-price" name="items[{{ $index }}][unit_price]" value="{{ $saleItem['unit_price'] ?? '' }}" placeholder="Harga SKU" inputmode="numeric" aria-label="Harga jual">
          <button class="sale-item-remove" type="button" aria-label="Hapus item" title="Hapus item">×</button>
        </div>
      @endforeach
    </div>
    <template id="sale-item-template">
      <div class="sale-item-row">
        <select name="items[__INDEX__][product_variant_id]" class="js-searchable sale-variant" data-placeholder="Cari SKU / produk…" required>
          <option value="">Pilih SKU</option>
          @foreach($variants as $variant)
            <option value="{{ $variant->id }}" data-price="{{ $variant->sell_price }}" data-stock="{{ $variant->stock }}">
              {{ $variant->product->name }} · {{ $variant->label }} · stok {{ $variant->stock }} · {{ $variant->sell_price_formatted }}
            </option>
          @endforeach
        </select>
        <input class="sale-qty" type="number" name="items[__INDEX__][quantity]" min="1" value="1" aria-label="Jumlah laku" required>
        <input class="sale-price" name="items[__INDEX__][unit_price]" placeholder="Harga SKU" inputmode="numeric" aria-label="Harga jual">
        <button class="sale-item-remove" type="button" aria-label="Hapus item" title="Hapus item">×</button>
      </div>
    </template>
  </div>

  <div class="full">
    <div class="sale-form-foot">
      <div class="sale-estimate">
        <span>Estimasi total</span>
        <b id="sale-estimate">Rp0</b>
      </div>
      <div class="form-actions">
      <button class="btn gray" type="reset">Bersihkan</button>
      <button class="btn" type="submit">@include('admin.partials.icon', ['name' => 'cart']) Simpan penjualan</button>
      </div>
    </div>
  </div>
</form>
</div>

<div class="panel table-panel">
<div class="panel-head table-toolbar">
  <span class="panel-title">@include('admin.partials.icon', ['name' => 'chart']) Riwayat transaksi</span>
  <form method="get" class="toolbar-form">
    <input type="hidden" name="from" value="{{ $from }}">
    <input type="hidden" name="to" value="{{ $to }}">
    <input name="q" value="{{ $search }}" placeholder="Cari SKU, pembeli, channel…">
    <button class="btn gray" type="submit">Cari</button>
    @if($search !== '')
      <a class="btn ghost" href="{{ route('admin.sales.index', ['from' => $from, 'to' => $to]) }}">Reset</a>
    @endif
  </form>
</div>
<div class="table-wrap">
<table class="table table-flat">
  <tr>
    <th>No. Order</th>
    <th>Tanggal</th>
    <th>Channel</th>
    <th>SKU / Varian</th>
    <th>Qty</th>
    <th>Jual</th>
    <th>HPP</th>
    <th>Laba kotor</th>
    <th>Pembeli</th>
    <th>Aksi</th>
  </tr>
  @forelse($orders as $order)
    @php $itemCount = max(1, $order->items->count()); @endphp
    @foreach($order->items as $item)
    <tr class="{{ $loop->first ? 'order-start' : '' }}">
      @if($loop->first)
        <td rowspan="{{ $itemCount }}" class="order-cell"><b>{{ $order->code }}</b><span>{{ $itemCount }} item</span></td>
        <td rowspan="{{ $itemCount }}" class="order-cell">{{ $order->sold_at->format('d/m/Y') }}</td>
        <td rowspan="{{ $itemCount }}" class="order-cell">{{ ucfirst($order->channel) }}</td>
      @endif
      <td>
        <div class="cell-stack">
          <b>{{ $item->product->name }}</b>
          <span>{{ $item->variant?->sku ?: $item->variant?->label }}</span>
        </div>
      </td>
      <td>{{ $item->quantity }}</td>
      <td>{{ $item->total_formatted }}</td>
      <td>Rp{{ number_format($item->cogs_total, 0, ',', '.') }}</td>
      <td class="profit">{{ $item->gross_profit_formatted }}</td>
      @if($loop->first)
        <td rowspan="{{ $itemCount }}" class="order-cell">{{ $order->customer_name ?: '—' }}</td>
      @endif
      <td>
        <div class="row-actions">
          @if($item->returnableQuantity() > 0)
            <a class="btn gray compact" href="{{ route('admin.returns.index', ['order_item_id' => $item->id]) }}">Retur</a>
          @endif
          @if($loop->first)
            @unless($order->items->sum(fn ($i) => $i->returns->count()))
            <form method="post" action="{{ route('admin.sales.destroy', $order) }}" onsubmit="return confirm('Hapus seluruh transaksi ini? Stok semua item akan dikembalikan.')">
              @csrf @method('DELETE')
              <button class="btn red compact" type="submit">Hapus order</button>
            </form>
            @endunless
          @endif
        </div>
      </td>
    </tr>
    @endforeach
  @empty
  <tr>
    <td colspan="10" class="empty-state">
      {{ $search !== '' ? 'Transaksi tidak ditemukan untuk pencarian ini.' : 'Belum ada penjualan di periode ini.' }}
    </td>
  </tr>
  @endforelse
</table>
</div>
<div class="panel-foot">
  Menampilkan {{ $visibleCount }} dari {{ $orderCount }} transaksi · {{ $periodLabel }}
</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  var form = document.getElementById('sale-form');
  var list = document.getElementById('sale-items');
  var template = document.getElementById('sale-item-template');
  var add = document.getElementById('add-sale-item');
  var estimate = document.getElementById('sale-estimate');
  if (!form || !list || !template || !add || !estimate) return;

  var nextIndex = {{ count($saleItems) }};
  var rupiah = new Intl.NumberFormat('id-ID');

  function selectedOption(row) {
    var select = row.querySelector('.sale-variant');
    return select && select.options[select.selectedIndex];
  }

  function updateEstimate() {
    var total = 0;
    list.querySelectorAll('.sale-item-row').forEach(function (row) {
      var option = selectedOption(row);
      var quantity = Number(row.querySelector('.sale-qty').value || 0);
      var custom = String(row.querySelector('.sale-price').value || '').replace(/\D/g, '');
      var price = custom === '' ? Number(option && option.dataset.price || 0) : Number(custom);
      total += quantity * price;
    });
    estimate.textContent = 'Rp' + rupiah.format(total);
  }

  function updateRemoveButtons() {
    var rows = list.querySelectorAll('.sale-item-row');
    rows.forEach(function (row) {
      row.querySelector('.sale-item-remove').disabled = rows.length === 1;
    });
  }

  function bindRow(row) {
    var select = row.querySelector('.sale-variant');
    initSearchable(select);
    if (select.tomselect) select.tomselect.on('change', updateEstimate);
    row.querySelector('.sale-qty').addEventListener('input', updateEstimate);
    row.querySelector('.sale-price').addEventListener('input', updateEstimate);
    row.querySelector('.sale-item-remove').addEventListener('click', function () {
      if (list.querySelectorAll('.sale-item-row').length === 1) return;
      if (select.tomselect) select.tomselect.destroy();
      row.remove();
      updateRemoveButtons();
      updateEstimate();
    });
  }

  add.addEventListener('click', function () {
    var wrapper = document.createElement('div');
    wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++)).trim();
    var row = wrapper.firstElementChild;
    list.appendChild(row);
    bindRow(row);
    updateRemoveButtons();
    updateEstimate();
  });

  list.querySelectorAll('.sale-item-row').forEach(bindRow);
  form.addEventListener('reset', function () {
    window.setTimeout(function () {
      var rows = list.querySelectorAll('.sale-item-row');
      rows.forEach(function (row, index) {
        if (index === 0) return;
        var select = row.querySelector('.sale-variant');
        if (select.tomselect) select.tomselect.destroy();
        row.remove();
      });
      updateRemoveButtons();
      updateEstimate();
    }, 0);
  });
  updateRemoveButtons();
  updateEstimate();
})();
</script>
@endpush
