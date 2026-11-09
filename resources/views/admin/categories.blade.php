@extends('layouts.app')
@section('title', 'Kelola Kategori')
@section('content')
<h1>Kategori</h1><p class="muted">Kategori awal adalah pengelompokan contoh untuk demo.</p>
<form method="post" action="{{ route('admin.categories.store') }}" class="panel inline-form">@csrf<div><label for="new-name">Nama kategori baru</label><input id="new-name" name="name" maxlength="100" required></div><button type="submit">Tambah kategori</button></form>
@foreach($categories as $category)<section class="panel category-row"><form method="post" action="{{ route('admin.categories.update', $category) }}" class="inline-form">@csrf @method('PUT')<div><label for="name-{{ $category->id }}">Nama kategori ({{ $category->products_count }} produk)</label><input id="name-{{ $category->id }}" name="name" value="{{ $category->name }}" maxlength="100" required></div><button class="secondary" type="submit">Simpan</button></form><form method="post" action="{{ route('admin.categories.destroy', $category) }}">@csrf @method('DELETE')<button class="link-button danger-text" type="submit" @disabled($category->products_count > 0)>Hapus kategori kosong</button></form></section>@endforeach
@endsection