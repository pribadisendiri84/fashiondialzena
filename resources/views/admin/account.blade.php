@extends('admin.layout')
@section('title', 'Akun')
@section('content')
<h1>Akun</h1>
<p class="sub">Ganti password untuk {{ $user->email }}. Peran: {{ $user->resolvedRole()->label() }}.</p>
<form class="form" method="post" action="{{ route('admin.account.update') }}">
  @csrf @method('PUT')
  <div class="full">
    <label>Password saat ini</label>
    @include('admin.partials.password-field', ['name' => 'current_password', 'required' => true, 'autocomplete' => 'current-password'])
  </div>
  <div>
    <label>Password baru</label>
    @include('admin.partials.password-field', ['name' => 'password', 'required' => true, 'minlength' => 8, 'autocomplete' => 'new-password'])
  </div>
  <div>
    <label>Ulangi password baru</label>
    @include('admin.partials.password-field', ['name' => 'password_confirmation', 'required' => true, 'minlength' => 8, 'autocomplete' => 'new-password'])
  </div>
  <div class="full"><button class="btn" type="submit">Simpan password</button></div>
</form>
@endsection
