<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login Admin — ALZena Fashion</title>
<link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login-body">
<form class="login-card" method="post" action="{{ route('admin.login.store') }}">
  @csrf
  <img class="login-logo" src="{{ asset('images/logo.png') }}" alt="ALZena Fashion">
  <p>Masuk ke panel admin</p>
  @if($errors->any())<div class="alert err">{{ $errors->first() }}</div>@endif
  <label>Email</label>
  <input type="email" name="email" value="{{ old('email') }}" required autofocus>
  <label>Password</label>
  <input type="password" name="password" required>
  <button class="btn" type="submit">Masuk</button>
</form>
</body>
</html>
