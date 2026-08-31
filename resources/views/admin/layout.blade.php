<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Admin') — ALZena Fashion</title>
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.default.min.css">
</head>
<body>
<div class="shell">
<aside class="sidebar" id="sidebar">
  <a class="side-brand" href="{{ route(auth()->user()->adminHomeRouteName()) }}">
    <img src="{{ asset('images/logo-mark.png') }}" alt="">
    <span>ALZena<small>Temukan Fashion-mu di Sini</small></span>
  </a>

  <nav class="side-nav">
    @php
      $menu = [
        ['route' => 'admin.dashboard', 'href' => route('admin.dashboard'), 'label' => 'Dashboard', 'icon' => 'home', 'ability' => 'view-dashboard'],
        ['route' => 'admin.ledger', 'href' => route('admin.ledger'), 'label' => 'Pembukuan', 'icon' => 'chart', 'ability' => 'view-financials'],
        ['route' => 'admin.products.*', 'href' => route('admin.products.index'), 'label' => 'Produk', 'icon' => 'box', 'ability' => 'manage-catalog'],
        ['route' => 'admin.sales.*', 'href' => route('admin.sales.index'), 'label' => 'Penjualan', 'icon' => 'cart', 'ability' => 'record-sales'],
        ['route' => 'admin.stock-ins.*', 'href' => route('admin.stock-ins.index'), 'label' => 'Stok masuk', 'icon' => 'inbox', 'ability' => 'record-stock'],
        ['route' => 'admin.returns.*', 'href' => route('admin.returns.index'), 'label' => 'Retur & Refund', 'icon' => 'undo', 'ability' => 'record-returns'],
        ['route' => 'admin.categories.*', 'href' => route('admin.categories.index'), 'label' => 'Kategori', 'icon' => 'tag', 'ability' => 'manage-catalog'],
        ['route' => 'admin.users.*', 'href' => route('admin.users.index'), 'label' => 'Pengguna', 'icon' => 'users', 'ability' => 'manage-users'],
        ['route' => 'admin.settings.*', 'href' => route('admin.settings.edit'), 'label' => 'Pengaturan', 'icon' => 'gear', 'ability' => 'manage-settings'],
      ];
    @endphp
    @foreach($menu as $item)
      @if(empty($item['ability']) || auth()->user()->can($item['ability']))
      <a href="{{ $item['href'] }}" class="{{ request()->routeIs($item['route']) ? 'active' : '' }}">
        @include('admin.partials.icon', ['name' => $item['icon']])
        {{ $item['label'] }}
      </a>
      @endif
    @endforeach
  </nav>

  <div class="side-foot">
    @auth
    <a class="side-user" href="{{ route('admin.account.edit') }}">
      <b>{{ auth()->user()->name }}</b>
      <span>{{ auth()->user()->resolvedRole()->label() }} · ganti password</span>
    </a>
    @endauth
    <a class="side-link" href="{{ route('home') }}" target="_blank">
      @include('admin.partials.icon', ['name' => 'external'])
      Lihat website
    </a>
    <form method="post" action="{{ route('admin.logout') }}">
      @csrf
      <button class="btn gray full-btn" type="submit">Keluar</button>
    </form>
  </div>
</aside>

<div class="content">
  <div class="mobile-bar">
    <button class="btn gray" type="button" onclick="document.getElementById('sidebar').classList.toggle('open')">☰ Menu</button>
    <span class="brand">ALZena Admin</span>
  </div>
  <main class="wrap">
    @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
    @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
    @yield('content')
  </main>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
<script>
function initSearchable(el) {
  if (!el || el.tomselect) return;
  new TomSelect(el, {
    create: false,
    allowEmptyOption: true,
    maxOptions: 500,
    placeholder: el.dataset.placeholder || 'Cari lalu pilih…',
    sortField: { field: 'text', direction: 'asc' },
  });
}

document.querySelectorAll('select.js-searchable').forEach(initSearchable);

document.addEventListener('reset', function (event) {
  window.setTimeout(function () {
    event.target.querySelectorAll('select').forEach(function (select) {
      if (select.tomselect) select.tomselect.setValue(select.value, true);
    });
  }, 0);
});

function pickPhoto(id) {
  var el = document.getElementById(id);
  if (el) el.click();
}

function compressPhoto(file) {
  if (!file) return Promise.resolve(null);
  if (file.size > 25 * 1024 * 1024) return Promise.resolve(file);

  return new Promise(function (resolve) {
    var url = URL.createObjectURL(file);
    var img = new Image();
    img.onload = function () {
      var max = 1600;
      var w = img.width;
      var h = img.height;
      if (w > max || h > max) {
        var scale = max / Math.max(w, h);
        w = Math.round(w * scale);
        h = Math.round(h * scale);
      }
      var canvas = document.createElement('canvas');
      canvas.width = w;
      canvas.height = h;
      canvas.getContext('2d').drawImage(img, 0, 0, w, h);
      URL.revokeObjectURL(url);
      canvas.toBlob(function (blob) {
        if (!blob) return resolve(file);
        resolve(new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), { type: 'image/jpeg' }));
      }, 'image/jpeg', 0.8);
    };
    img.onerror = function () {
      URL.revokeObjectURL(url);
      resolve(file);
    };
    img.src = url;
  });
}

function kb(size) {
  return Math.max(1, Math.round(size / 1024));
}

function showPhotoPreview(destId, src) {
  var img = document.getElementById(destId + '_preview');
  var empty = document.getElementById(destId + '_empty');
  var wrap = document.getElementById(destId + '_wrap');
  var clear = document.getElementById(destId + '_clear');
  if (!img) return;
  if (src) {
    img.src = src;
    img.hidden = false;
    if (empty) empty.hidden = true;
    if (wrap) wrap.classList.add('filled');
    if (clear) clear.hidden = false;
  } else {
    img.removeAttribute('src');
    img.hidden = true;
    if (empty) empty.hidden = false;
    if (wrap) wrap.classList.remove('filled');
    if (clear) clear.hidden = true;
  }
}

function clearPhoto(destId) {
  var dest = document.getElementById(destId);
  var cam = document.getElementById(destId + '_cam');
  var label = document.getElementById(destId + '_name');
  var url = document.getElementById(destId === 'photo_front' ? 'img_front' : 'img_back');
  if (dest) dest.value = '';
  if (cam) cam.value = '';
  if (url) url.value = '';
  if (label) label.textContent = 'Foto dikompres otomatis saat dipilih. Cek preview, lalu Simpan.';
  showPhotoPreview(destId, '');
}

function applyCompressedPhoto(dest, file, label) {
  if (!file || dest.dataset.compressing === '1') return;
  dest.dataset.compressing = '1';
  if (label) label.textContent = 'Mengompres ' + file.name + '…';
  compressPhoto(file).then(function (ready) {
    dest.dataset.compressing = '0';
    if (!ready) return;
    var dt = new DataTransfer();
    dt.items.add(ready);
    dest.files = dt.files;
    dest.removeAttribute('required');
    showPhotoPreview(dest.id, URL.createObjectURL(ready));
    if (label) {
      label.textContent = ready.name + ' (' + kb(file.size) + ' KB → ' + kb(ready.size) + ' KB). Cek preview, lalu Simpan.';
    }
  }).catch(function () {
    dest.dataset.compressing = '0';
    var dt = new DataTransfer();
    dt.items.add(file);
    dest.files = dt.files;
    showPhotoPreview(dest.id, URL.createObjectURL(file));
    if (label) label.textContent = file.name;
  });
}

function bindPhotoInputs(camId, destId, nameId) {
  var cam = document.getElementById(camId);
  var dest = document.getElementById(destId);
  var label = document.getElementById(nameId);
  if (!dest) return;

  dest.addEventListener('change', function () {
    if (dest.files[0]) applyCompressedPhoto(dest, dest.files[0], label);
  });

  if (!cam) return;
  cam.addEventListener('change', function () {
    if (cam.files[0]) applyCompressedPhoto(dest, cam.files[0], label);
  });
}

bindPhotoInputs('photo_front_cam', 'photo_front', 'photo_front_name');
bindPhotoInputs('photo_back_cam', 'photo_back', 'photo_back_name');

['img_front', 'img_back'].forEach(function (id) {
  var el = document.getElementById(id);
  if (!el) return;
  el.addEventListener('input', function () {
    var destId = id === 'img_front' ? 'photo_front' : 'photo_back';
    showPhotoPreview(destId, el.value.trim());
  });
});
</script>
@include('admin.partials.password-toggle-script')
@stack('scripts')
</body>
</html>
