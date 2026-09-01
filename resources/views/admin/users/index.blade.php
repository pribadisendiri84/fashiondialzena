@extends('admin.layout')
@section('title', 'Pengguna')
@section('content')
<h1>Pengguna</h1>
<p class="sub">Hanya superadmin yang bisa menambah pengguna. Admin, staf, dan penjualan tidak bisa membuat akun baru.</p>
@include('admin.partials.scope-tabs')

@unless($trashed)
<form class="form" method="post" action="{{ route('admin.users.store') }}">
  @csrf
  <div>
    <label>Nama</label>
    <input name="name" value="{{ old('name') }}" required>
  </div>
  <div>
    <label>Email</label>
    <input type="email" name="email" value="{{ old('email') }}" required>
  </div>
  <div>
    <label>Password</label>
    @include('admin.partials.password-field', ['name' => 'password', 'required' => true, 'minlength' => 8, 'autocomplete' => 'new-password'])
  </div>
  <div>
    <label>Peran</label>
    <select name="role" required>
      @foreach($roles as $role)
        <option value="{{ $role->value }}" @selected(old('role', 'sales') === $role->value)>{{ $role->label() }}</option>
      @endforeach
    </select>
  </div>
  <div class="full"><button class="btn" type="submit">Tambah pengguna</button></div>
</form>
@endunless

<table class="table" style="margin-top:18px">
  <tr><th>Nama</th><th>Email</th><th>Peran</th><th>Password baru</th><th></th></tr>
  @forelse($users as $user)
  <tr class="{{ $user->trashed() ? 'is-deleted' : '' }}">
    @if($user->trashed())
      <td><b>{{ $user->name }}</b></td>
      <td>{{ $user->email }}</td>
      <td>{{ $user->resolvedRole()->label() }}</td>
      <td>@include('admin.partials.timestamps', ['model' => $user])</td>
      <td>
        @include('admin.partials.row-actions', [
          'item' => $user,
          'restore' => route('admin.users.restore', $user),
        ])
      </td>
    @else
    <td colspan="4">
      <form method="post" action="{{ route('admin.users.update', $user) }}" class="form" style="grid-template-columns:repeat(4,minmax(0,1fr));margin:0">
        @csrf @method('PUT')
        <div>
          <input name="name" value="{{ old('name', $user->name) }}" required>
        </div>
        <div>
          <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
        </div>
        <div>
          <select name="role" required>
            @foreach($roles as $role)
              <option value="{{ $role->value }}" @selected(old('role', $user->resolvedRole()->value) === $role->value)>{{ $role->label() }}</option>
            @endforeach
          </select>
        </div>
        <div>
          @include('admin.partials.password-field', ['name' => 'password', 'minlength' => 8, 'placeholder' => 'Kosong = tidak diubah', 'autocomplete' => 'new-password'])
        </div>
        <div class="full"><button class="btn gray" type="submit">Update</button></div>
      </form>
    </td>
    <td>
      @unless($user->is(auth()->user()))
      <form method="post" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Hapus pengguna ini?')">
        @csrf @method('DELETE')
        <button class="btn red" type="submit">Hapus</button>
      </form>
      @endunless
    </td>
    @endif
  </tr>
  @empty
  <tr><td colspan="5" class="empty-state">{{ $trashed ? 'Tidak ada pengguna terhapus.' : 'Belum ada pengguna.' }}</td></tr>
  @endforelse
</table>
@endsection
