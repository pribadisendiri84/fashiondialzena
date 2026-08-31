@extends('admin.layout')
@section('title', 'Kategori')
@section('content')
<h1>Kategori</h1>
<p class="sub">Kategori tampil di halaman utama. Gambar memakai link.</p>
<form class="form" method="post" action="{{ route('admin.categories.store') }}">
  @csrf
  <div>
    <label>Nama kategori</label>
    <input name="name" required>
  </div>
  <div>
    <label>Urutan</label>
    <input type="number" name="sort_order" value="0">
  </div>
  <div class="full">
    <label>Link gambar</label>
    <input name="image_url" placeholder="https://...">
  </div>
  <div class="full"><button class="btn" type="submit">Tambah kategori</button></div>
</form>

<table class="table" style="margin-top:18px">
  <tr><th>Gambar</th><th>Nama</th><th>Urutan</th><th></th></tr>
  @foreach($categories as $category)
  <tr>
    <td>@if($category->image_url)<img class="thumb" src="{{ $category->image_url }}" alt="">@endif</td>
    <td>
      <form method="post" action="{{ route('admin.categories.update', $category) }}">
        @csrf @method('PUT')
        <input name="name" value="{{ $category->name }}" style="max-width:180px">
        <input type="number" name="sort_order" value="{{ $category->sort_order }}" style="max-width:70px">
        <input name="image_url" value="{{ $category->image_url }}" placeholder="https://...">
        <button class="btn gray" type="submit">Update</button>
      </form>
    </td>
    <td>{{ $category->sort_order }}</td>
    <td>
      @can('delete-records')
      <form method="post" action="{{ route('admin.categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?')">
        @csrf @method('DELETE')
        <button class="btn red" type="submit">Hapus</button>
      </form>
      @endcan
    </td>
  </tr>
  @endforeach
</table>
@endsection
