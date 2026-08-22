@extends('admin.layout')
@section('title', 'Pengaturan')
@section('content')
<h1>Pengaturan</h1>
<p class="sub">Nomor WhatsApp dipakai tombol Pesan di website.</p>
<form class="form" method="post" action="{{ route('admin.settings.update') }}">
  @csrf @method('PUT')
  <div class="full">
    <label>Nomor WhatsApp (format 62...)</label>
    <input name="wa_number" value="{{ old('wa_number', $wa) }}" required>
  </div>
  <div class="full"><button class="btn" type="submit">Simpan</button></div>
</form>
@endsection
