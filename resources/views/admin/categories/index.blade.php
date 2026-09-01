@extends('admin.layout')
@section('title', 'Kategori')
@section('content')
<h1>Kategori</h1>
<p class="sub">Kategori tampil di halaman utama. Gambar memakai link.</p>
@include('admin.partials.scope-tabs')

@unless($trashed)
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
@endunless

<table class="table" style="margin-top:18px">
  <tr><th>Gambar</th><th>Nama</th><th>Urutan</th><th>Waktu</th><th></th></tr>
  @forelse($categories as $category)
  <tr class="{{ $category->trashed() ? 'is-deleted' : '' }}">
    <td>@if($category->image_url)<img class="thumb" src="{{ $category->image_url }}" alt="">@endif</td>
    <td>
      @if($category->trashed())
        <b>{{ $category->name }}</b>
      @else
      <form method="post" action="{{ route('admin.categories.update', $category) }}">
        @csrf @method('PUT')
        <input name="name" value="{{ $category->name }}" style="max-width:180px">
        <input type="number" name="sort_order" value="{{ $category->sort_order }}" style="max-width:70px">
        <input name="image_url" value="{{ $category->image_url }}" placeholder="https://...">
        <button class="btn gray" type="submit">Update</button>
      </form>
      @endif
    </td>
    <td>{{ $category->sort_order }}</td>
    <td>@include('admin.partials.timestamps', ['model' => $category])</td>
    <td>
      @include('admin.partials.row-actions', [
        'item' => $category,
        'destroy' => auth()->user()?->can('delete-records') ? route('admin.categories.destroy', $category) : null,
        'restore' => route('admin.categories.restore', $category),
        'confirm' => 'Hapus kategori ini? Superadmin bisa memulihkan.',
      ])
    </td>
  </tr>
  @empty
  <tr><td colspan="5" class="empty-state">{{ $trashed ? 'Tidak ada kategori terhapus.' : 'Belum ada kategori.' }}</td></tr>
  @endforelse
</table>
@endsection
