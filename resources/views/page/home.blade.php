@extends('layouts.app')
@section('title', 'Perlengkapan & Jok Custom')
@section('content')
<section class="hero">
    <div>
        <p class="eyebrow">J2M / PERLENGKAPAN JOK MOTOR</p>
        <h1>Dari bahan jok,<br>sampai satu set custom.</h1>
        <p>J2M menyediakan kebutuhan bahan jok motor dan layanan custom jok dalam satu tempat.</p>
        <a class="button" href="{{ route('catalog') }}">Lihat katalog</a>
    </div>
    <div class="hero-panel">
        <span>JOK<br>MOTOR</span>
        <p>Bahan jok motor dan jasa custom jok.</p>
    </div>
</section>

<section aria-labelledby="category-heading">
    <div class="section-heading">
        <h2 id="category-heading">Kategori produk</h2>
        <nav aria-label="Kategori produk">
            @foreach($categories as $category)
                <a class="text-link" href="{{ route('catalog', ['category' => $category->id]) }}">{{ $category->name }}</a>
            @endforeach
        </nav>
    </div>
</section>

<section>
    <div class="section-heading">
        <div><p class="eyebrow">KATALOG J2M</p><h2>Perlengkapan pilihan Anda</h2></div>
        <a class="text-link" href="{{ route('catalog') }}">Semua produk →</a>
    </div>
    <div class="product-grid">
        @forelse($products as $product)
            @include('partials.product-card')
        @empty
            <p>Produk belum tersedia.</p>
        @endforelse
    </div>
</section>

<section id="custom-jok" class="info-strip">
    <h2>Jasa custom jok</h2>
    <p>J2M menyediakan layanan custom jok motor. Lihat produk Satu Set Jok Custom di katalog untuk mengetahui harga dan mencoba pemesanan simulasi.</p>
</section>

<section id="tentang-j2m" class="info-strip">
    <h2>Tentang J2M</h2>
    <p>J2M (Jok Motor) menyediakan bahan jok motor dan jasa custom jok untuk pengguna sepeda motor. Pilihan produk mencakup busa, fiber atau tulangan jok, kulit jok biasa, kulit jok motif, dan latex.</p>
</section>
@endsection