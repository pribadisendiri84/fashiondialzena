<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'Admin') — FashionDialZena</title>
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.default.min.css">
</head>
<body>
<header class="topbar">
  <div class="brand">FashionDialZena Admin</div>
  <nav class="nav">
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">Dashboard</a>
    <a href="{{ route('admin.sales.index') }}" class="{{ request()->routeIs('admin.sales.*') ? 'active' : '' }}">Penjualan</a>
    <a href="{{ route('admin.returns.index') }}" class="{{ request()->routeIs('admin.returns.*') ? 'active' : '' }}">Retur</a>
    <a href="{{ route('admin.stock-ins.index') }}" class="{{ request()->routeIs('admin.stock-ins.*') ? 'active' : '' }}">Stok masuk</a>
    <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">Produk</a>
    <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">Kategori</a>
    <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">Pengaturan</a>
    <a href="{{ route('home') }}" target="_blank">Lihat Website</a>
    <form method="post" action="{{ route('admin.logout') }}" style="display:inline">
      @csrf
      <button class="btn gray" type="submit">Keluar</button>
    </form>
  </nav>
</header>
<main class="wrap">
  @if(session('ok'))<div class="alert ok">{{ session('ok') }}</div>@endif
  @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
  @yield('content')
</main>
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
<script>
document.querySelectorAll('select.js-searchable').forEach(function (el) {
  new TomSelect(el, {
    create: false,
    allowEmptyOption: true,
    maxOptions: 500,
    placeholder: el.dataset.placeholder || 'Cari lalu pilih…',
    sortField: { field: 'text', direction: 'asc' },
  });
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
    if (label) {
      label.textContent = ready.name + ' (' + kb(file.size) + ' KB → ' + kb(ready.size) + ' KB)';
    }
  }).catch(function () {
    dest.dataset.compressing = '0';
    var dt = new DataTransfer();
    dt.items.add(file);
    dest.files = dt.files;
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
</script>
</body>
</html>
