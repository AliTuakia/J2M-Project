<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Perlengkapan Jok Motor') — J2M</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <a class="skip-link" href="#main">Lewati ke konten</a>
    @include('layouts.partials.header')
    <main id="main" class="container main">
        @if(session('message'))<div class="notice" role="status">{{ session('message') }}</div>@endif
        @if($errors->any())
            <div class="error-box" role="alert"><strong>Periksa kembali isian Anda.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        @if(request()->routeIs('admin.*'))
            <nav class="admin-nav" aria-label="Navigasi admin">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a><a href="{{ route('admin.products') }}">Produk</a><a href="{{ route('admin.categories') }}">Kategori</a><a href="{{ route('admin.orders') }}">Pesanan</a>
            </nav>
        @endif
        @yield('content')
    </main>
    @include('layouts.partials.footer')
</body>
</html>