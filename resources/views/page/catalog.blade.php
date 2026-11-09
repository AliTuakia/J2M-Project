@extends('layouts.app')
@section('title', 'Katalog Produk')
@section('content')
<p class="eyebrow">J2M / KATALOG</p><h1>Produk</h1><p class="muted">Temukan bahan jok motor dan satu set jok custom. Harga dalam Rupiah.</p>
<form method="get" action="{{ route('catalog') }}" class="filter-bar"><div><label for="q">Cari produk</label><input id="q" name="q" value="{{ request('q') }}" maxlength="100" placeholder="Contoh: kulit jok"></div><div><label for="category">Kategori</label><select id="category" name="category"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>@endforeach</select></div><button type="submit">Tampilkan</button><a href="{{ route('catalog') }}">Reset</a></form>
<div class="product-grid">@forelse($products as $product)@include('partials.product-card')@empty<div class="empty-state"><h2>Produk tidak ditemukan</h2><p>Coba kata kunci atau kategori lain.</p></div>@endforelse</div>
@include('partials.pagination', ['paginator' => $products])
@endsection