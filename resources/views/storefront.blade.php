<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#c45c7a">
<meta name="description" content="Katalog FashionDialZena. Lihat produk dan pesan via WhatsApp.">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="DialZena">
<title>FashionDialZena — Fashion Store</title>
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" href="{{ asset('icons/icon-192.png') }}" type="image/png" sizes="192x192">
<link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
<link rel="stylesheet" href="{{ asset('css/store.css') }}">
</head>
<body>
<div class="top">
  <div>✨ Fashion pilihan — order langsung via WhatsApp</div>
  <div><a href="https://wa.me/{{ $wa }}" target="_blank">Chat WhatsApp →</a></div>
</div>
<nav class="nav">
  <div class="logo">FashionDialZena<small>Your daily style</small></div>
  <div class="menu">
    <a href="#katalog">Katalog</a>
    <a href="#new">Terbaru</a>
    <a href="#best">Best Seller</a>
    <a href="#featured">Best Product</a>
  </div>
  <div class="search">⌕ <input id="search" placeholder="Cari produk..." oninput="filterProducts()"></div>
</nav>

<main>
<section class="hero">
  <div class="hero-copy">
    <span class="eyebrow">KOLEKSI 2026</span>
    <h1>Elegan.<br>Feminin.<br>Stylish.</h1>
    <p>Koleksi fashion pilihan FashionDialZena. Lihat tampilan depan & belakang, pesan via WhatsApp.</p>
    <div>
      <button class="btn" onclick="document.getElementById('katalog').scrollIntoView({behavior:'smooth'})">Lihat Katalog →</button>
      <a class="btn light" href="https://wa.me/{{ $wa }}?text={{ rawurlencode('Halo FashionDialZena 👋\n\nSaya ingin bertanya tentang produk & katalog. Terima kasih!') }}" target="_blank">Tanya Admin</a>
    </div>
  </div>
  <div class="hero-img"></div>
</section>

<div class="benefits">
  <div class="benefit">💬<b>Order via WA</b><small>Langsung & praktis</small></div>
  <div class="benefit">✓<b>Produk Pilihan</b><small>Kualitas terjamin</small></div>
  <div class="benefit">🚚<b>Kirim Seluruh Indonesia</b><small>Via ekspedisi</small></div>
  <div class="benefit">🔒<b>Transfer · QRIS · COD</b><small>Pembayaran fleksibel</small></div>
</div>

<section id="kategori">
  <div class="section-head"><h2>Kategori</h2></div>
  <div class="categories">
    @forelse($categories as $category)
      <a class="cat" href="#katalog" onclick="filterByCategory('{{ $category->name }}')">
        @if($category->image_url)
          <img src="{{ $category->image_url }}" alt="{{ $category->name }}">
        @else
          <span class="cat-fallback">{{ mb_substr($category->name, 0, 1) }}</span>
        @endif
        {{ $category->name }}
      </a>
    @empty
      <p class="empty">Belum ada kategori.</p>
    @endforelse
  </div>
</section>

<section id="new">
  <div class="section-head"><h2>New Arrival</h2><p>Klik foto untuk perbesar · tombol Pesan untuk order</p></div>
  <div class="products">
    @forelse($newArrival as $product)
      @include('partials.product-card', ['product' => $product, 'wa' => $wa])
    @empty
      <p class="empty">Belum ada New Arrival.</p>
    @endforelse
  </div>
</section>

<section id="best">
  <div class="section-head"><h2>Best Seller</h2><p>Favorit pelanggan FashionDialZena</p></div>
  <div class="products">
    @forelse($bestSeller as $product)
      @include('partials.product-card', ['product' => $product, 'wa' => $wa])
    @empty
      <p class="empty">Belum ada Best Seller.</p>
    @endforelse
  </div>
</section>

<section id="featured">
  <div class="section-head"><h2>Best Product</h2><p>Pilihan terbaik dari FashionDialZena</p></div>
  <div class="products">
    @forelse($featured as $product)
      @include('partials.product-card', ['product' => $product, 'wa' => $wa])
    @empty
      <p class="empty">Belum ada Best Product.</p>
    @endforelse
  </div>
</section>

<section id="katalog">
  <div class="section-head"><h2>Semua Produk</h2><p>Katalog lengkap FashionDialZena</p></div>
  <div class="products" id="allProducts">
    @forelse($products as $product)
      @include('partials.product-card', ['product' => $product, 'wa' => $wa])
    @empty
      <p class="empty">Belum ada produk. Tambah dari halaman admin.</p>
    @endforelse
  </div>
</section>
</main>

<footer class="footer">
  <div><h3>FashionDialZena</h3><p>Fashion elegan untuk keseharianmu. Pesan langsung via WhatsApp, tanpa ribet.</p></div>
  <div><b>Katalog</b><a href="#new">Terbaru</a><a href="#best">Best Seller</a><a href="#featured">Best Product</a><a href="#katalog">Semua Produk</a></div>
  <div><b>Hubungi</b><a href="https://wa.me/{{ $wa }}" target="_blank">WhatsApp</a><p>Transfer · QRIS · COD</p><p>© {{ date('Y') }} FashionDialZena</p></div>
</footer>

<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <div class="lightbox-box" onclick="event.stopPropagation()">
    <button class="lightbox-close" type="button" onclick="closeLightbox()">&times;</button>
    <h3 id="lbTitle"></h3>
    <p class="price" id="lbPrice"></p>
    <div class="lightbox-duo">
      <div><img id="lbFront" alt=""><span>Depan</span></div>
      <div><img id="lbBack" alt=""><span>Belakang</span></div>
    </div>
  </div>
</div>
<script>
function openLightbox(el){
  document.getElementById('lbTitle').textContent=el.dataset.name;
  document.getElementById('lbPrice').textContent=el.dataset.price;
  document.getElementById('lbFront').src=el.dataset.front;
  document.getElementById('lbBack').src=el.dataset.back;
  document.getElementById('lightbox').classList.add('open');
}
function closeLightbox(){document.getElementById('lightbox').classList.remove('open')}
function filterByCategory(name){document.getElementById('search').value=name;filterProducts();document.getElementById('katalog').scrollIntoView({behavior:'smooth'})}
function filterProducts(){
  const q=document.getElementById('search').value.toLowerCase();
  document.querySelectorAll('#allProducts .product').forEach(x=>x.style.display=x.dataset.name.includes(q)?'':'none');
}
document.addEventListener('keydown',e=>{if(e.key==='Escape')closeLightbox()});

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
}

(function () {
  if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone) {
    return;
  }
  if (localStorage.getItem('pwa-install-dismissed')) {
    return;
  }

  const banner = document.createElement('div');
  banner.className = 'pwa-banner';
  const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
  banner.innerHTML = isIos
    ? '<span>Pasang aplikasi: ketuk <b>Bagikan</b> lalu <b>Add to Home Screen</b></span><button type="button" class="pwa-dismiss" aria-label="Tutup">×</button>'
    : '<span>Pasang FashionDialZena di layar utama</span><div><button type="button" class="pwa-install">Pasang</button><button type="button" class="pwa-dismiss" aria-label="Tutup">×</button></div>';
  document.body.appendChild(banner);

  let deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    deferredPrompt = event;
    banner.classList.add('show');
  });

  if (isIos) {
    banner.classList.add('show');
  }

  banner.querySelector('.pwa-dismiss').addEventListener('click', () => {
    banner.classList.remove('show');
    localStorage.setItem('pwa-install-dismissed', '1');
  });

  const installBtn = banner.querySelector('.pwa-install');
  if (installBtn) {
    installBtn.addEventListener('click', async () => {
      if (!deferredPrompt) {
        return;
      }
      deferredPrompt.prompt();
      await deferredPrompt.userChoice;
      deferredPrompt = null;
      banner.classList.remove('show');
    });
  }
})();
</script>
</body>
</html>
