@extends('layouts.app')
@section('title', $product->exists ? 'Ubah Produk' : 'Tambah Produk')
@section('content')
<h1>{{ $product->exists ? 'Ubah produk' : 'Tambah produk' }}</h1><p class="muted">Foto masih menggunakan placeholder berlabel. Jangan menambahkan klaim bisnis yang belum dikonfirmasi.</p>
<form method="post" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" class="panel stack form-width">@csrf @if($product->exists)@method('PUT')@endif
<div><label for="name">Nama produk</label><input id="name" name="name" maxlength="255" value="{{ old('name', $product->name) }}" required></div>
<div><label for="category_id">Kategori</label><select id="category_id" name="category_id" required><option value="">Pilih kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>@endforeach</select></div>
<div><label for="description">Deskripsi</label><textarea id="description" name="description" rows="5" maxlength="5000" required>{{ old('description', $product->description) }}</textarea></div>
<div><label for="price">Harga (Rupiah, tanpa titik)</label><input id="price" name="price" type="number" min="0" max="1000000000" value="{{ old('price', $product->price) }}" required></div>
<div><label for="stock">Stok demo</label><input id="stock" name="stock" type="number" min="0" max="1000000" value="{{ old('stock', $product->stock ?? 10) }}" required></div>
<div><label for="is_active">Tampil di katalog</label><select id="is_active" name="is_active"><option value="1" @selected(old('is_active', $product->is_active) == 1)>Aktif</option><option value="0" @selected(old('is_active', $product->is_active) == 0)>Nonaktif</option></select></div>
<div><label for="requires_custom">Butuh tipe motor dan request custom?</label><select id="requires_custom" name="requires_custom"><option value="0" @selected(old('requires_custom', $product->requires_custom) == 0)>Tidak</option><option value="1" @selected(old('requires_custom', $product->requires_custom) == 1)>Ya</option></select></div>
<button type="submit">Simpan produk</button><a href="{{ route('admin.products') }}">Batal</a></form>
@endsection