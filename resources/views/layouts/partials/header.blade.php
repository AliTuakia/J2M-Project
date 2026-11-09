    <header class="site-header">
        <div class="container header-inner">
            <a class="wordmark" href="{{ route('home') }}" aria-label="J2M beranda">J2M<span>JOK MOTOR</span></a>
            <nav aria-label="Navigasi utama">
                <a href="{{ route('catalog') }}">Produk</a>
                @auth
                    <a href="{{ route('cart.index') }}">Keranjang</a>
                    <a href="{{ route('orders.index') }}">Pesanan saya</a>
                    @if(auth()->user()->role === 'admin')<a href="{{ route('admin.dashboard') }}">Admin</a>@endif
                    <form method="post" action="{{ route('logout') }}">@csrf<button class="link-button" type="submit">Keluar</button></form>
                @else
                    <a href="{{ route('login') }}">Masuk</a>
                    <a class="button small" href="{{ route('register') }}">Daftar</a>
                @endauth
            </nav>
        </div>
    </header>
