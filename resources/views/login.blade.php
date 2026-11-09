@extends('layouts.app')
@section('title', $register ? 'Daftar' : 'Masuk')
@section('content')
<section class="panel auth-panel"><p class="eyebrow">AKUN PELANGGAN J2M</p><h1>{{ $register ? 'Buat akun' : 'Selamat datang kembali' }}</h1><p class="muted">Gunakan data fiktif untuk demo. Jangan gunakan password akun penting Anda.</p>
<form method="post" action="{{ $register ? route('register') : route('login') }}" class="stack">@csrf
@if($register)<div><label for="name">Nama</label><input id="name" name="name" value="{{ old('name') }}" maxlength="100" autocomplete="name" required></div>@endif
<div><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required></div>
<div><label for="password">Password{{ $register ? ' (minimal 8 karakter)' : '' }}</label><input id="password" name="password" type="password" maxlength="255" autocomplete="{{ $register ? 'new-password' : 'current-password' }}" required></div>
@if($register)<div><label for="password_confirmation">Ulangi password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required></div>@endif
<button type="submit">{{ $register ? 'Daftar' : 'Masuk' }}</button></form>
<p>{{ $register ? 'Sudah punya akun?' : 'Belum punya akun?' }} <a class="text-link" href="{{ $register ? route('login') : route('register') }}">{{ $register ? 'Masuk' : 'Daftar sekarang' }}</a></p></section>
@endsection