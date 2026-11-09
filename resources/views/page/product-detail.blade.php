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
@endsection