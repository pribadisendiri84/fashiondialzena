@php
    $variantLines = $product->variants
        ->where('is_active', true)
        ->where('stock', '>', 0)
        ->map(function ($v) {
            $parts = collect([$v->color, $v->size])->filter()->implode('/');
            return trim($v->sku.($parts ? " ({$parts})" : '').' stok '.$v->stock);
        })
        ->values()
        ->all();
    $variantText = count($variantLines) ? implode("\n", $variantLines) : 'Stok habis / belum diisi';
    $waText = rawurlencode("Halo ALZena Fashion 👋\n\nSaya ingin pesan produk berikut:\n\n📦 Produk: {$product->name}\n🏷️ Kategori: {$product->category->name}\n💰 Harga: {$product->price_formatted}\n📋 SKU tersedia:\n{$variantText}\n\nMohon bantu proses pesanannya. Terima kasih!");
@endphp
<article class="product" data-name="{{ strtolower($product->name.' '.$product->category->name) }}">
  <div class="photo-duo" onclick="openLightbox(this)" data-name="{{ $product->name }}" data-price="{{ $product->price_formatted }}" data-front="{{ $product->img_front }}" data-back="{{ $product->img_back }}">
    @if($product->is_new)<span class="new">NEW</span>@endif
    <button class="zoom-btn" type="button" aria-label="Perbesar foto" onclick="event.stopPropagation();openLightbox(this.parentElement)">
      <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/></svg>
    </button>
    <div class="photo-half"><img src="{{ $product->img_front }}" alt="{{ $product->name }} — depan"><span class="photo-label">Depan</span></div>
    <div class="photo-half"><img src="{{ $product->img_back }}" alt="{{ $product->name }} — belakang"><span class="photo-label">Belakang</span></div>
  </div>
  <div class="product-info">
    <div class="product-meta">
      <h3>{{ $product->name }}</h3>
      <div class="price">{{ $product->price_formatted }}</div>
      @if($product->rating)<div class="rating">★★★★★ <span style="color:#777">({{ $product->rating }})</span></div>@endif
      @if($product->variants->where('is_active', true)->count() > 1)
        <div class="hint" style="margin-top:6px">{{ $product->variants->where('is_active', true)->where('stock', '>', 0)->count() }} SKU ready</div>
      @endif
    </div>
    @if($product->stock > 0)
      <a class="wa-product" href="https://wa.me/{{ $wa }}?text={{ $waText }}" target="_blank">
        <svg viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
        Pesan
      </a>
    @else
      <span class="wa-product sold">Habis</span>
    @endif
  </div>
</article>
