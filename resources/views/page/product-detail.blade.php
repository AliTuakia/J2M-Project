@extends('layouts.app')
@section('title', $product->name)
@section('content')
<a class="text-link" href="{{ route('catalog') }}">← Kembali ke katalog</a>
<div class="detail-grid">
@if($imagePath = $product->imagePath())
    <div class="product-photo-frame">
    <img class="product-photo product-photo-detail" src="{{ asset(str_replace(' ', '%20', $imagePath)) }}" alt="{{ $product->name }}" width="600" height="450">
        @if($product->usesIllustration())
            <span class="photo-label">FOTO HANYA ILUSTRASI</span>
        @endif
    </div>
@endif
<section><p class="eyebrow">{{ $product->category->name }}</p><h1>{{ $product->name }}</h1><p class="price large-price">Rp{{ number_format($product->price, 0, ',', '.') }}</p>
@include('partials.product-rating')
@if(filled($description))<p class="preserve-text">{{ $description }}</p>@endif
@if($product->usesIllustration())
    <p class="muted">Foto merupakan referensi visual, bukan desain yang wajib diikuti.</p>
@endif
<p class="muted">Pemesanan hanya simulasi. Tidak ada pembayaran atau pengiriman nyata.</p>
@auth
<form method="post" action="{{ route('cart.store', $product) }}" class="stack">@csrf
<div><label for="quantity">Jumlah</label><input id="quantity" name="quantity" type="number" min="1" max="99" value="{{ old('quantity', 1) }}" required></div>
@if($product->requires_custom)<div><label for="motorcycle_type">Tipe motor <span>(wajib)</span></label><input id="motorcycle_type" name="motorcycle_type" maxlength="100" value="{{ old('motorcycle_type') }}" required></div><div><label for="request_notes">Catatan request <span>(opsional)</span></label><textarea id="request_notes" name="request_notes" maxlength="1000" rows="4">{{ old('request_notes') }}</textarea><small>Dalam simulasi ini, catatan tidak mengubah harga yang tertera.</small></div>@endif
<button type="submit" @disabled($product->stock < 1)>{{ $product->stock > 0 ? 'Tambah ke keranjang' : 'Tidak tersedia untuk simulasi' }}</button></form>
@else
<p>Masuk atau daftar sebelum menambahkan produk ke keranjang.</p><a class="button" href="{{ route('products.login', $product) }}">Tambah ke keranjang · masuk</a>
@endauth
</section></div>
<section id="rating" class="panel product-rating-panel" aria-labelledby="rating-heading">
    <h2 id="rating-heading">Beri rating produk</h2>
    <p class="muted">Penilaian pengguna website simulasi J2M. Setiap akun dapat memberi satu rating per produk dan memperbaruinya.</p>
    @auth
        @if($errors->rating->any())
            <div class="error-box" role="alert">
                @foreach($errors->rating->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif
        <form method="post" action="{{ route('products.rating', $product) }}" class="stack">
            @csrf
            <fieldset class="rating-fieldset" aria-describedby="rating-help">
                <legend>Rating Anda</legend>
                <div class="rating-options">
                    @for($score = 1; $score <= 5; $score++)
                        <label class="rating-option" for="rating-{{ $score }}">
                            <input type="radio" id="rating-{{ $score }}" name="score" value="{{ $score }}" @checked(in_array(old('score', $userRating), [$score, (string) $score], true)) required>
                            <span>{{ $score }} <span aria-hidden="true">★</span><span class="sr-only"> bintang</span></span>
                        </label>
                    @endfor
                </div>
                <small id="rating-help">1 = sangat kurang, 5 = sangat baik.</small>
            </fieldset>
            <div><button type="submit">{{ $userRating ? 'Perbarui rating' : 'Kirim rating' }}</button></div>
        </form>
    @else
        <p>Masuk terlebih dahulu untuk memberikan rating.</p>
        <a class="button" href="{{ route('products.login', $product) }}">Masuk untuk memberi rating</a>
    @endauth
</section>
@endsection
