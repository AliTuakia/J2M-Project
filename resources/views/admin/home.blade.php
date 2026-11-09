@extends('layouts.app')
@section('title', 'Dashboard Admin')
@section('content')
<p class="eyebrow">AREA ADMIN</p><h1>Ringkasan toko demo</h1><p class="muted">Data di sini berasal dari aktivitas simulasi, bukan penjualan toko sesungguhnya.</p>
<div class="stat-grid"><section class="panel"><h2>Produk aktif</h2><p class="stat">{{ $productCount }}</p></section><section class="panel"><h2>Total pesanan</h2><p class="stat">{{ $orderCount }}</p></section>@foreach(\App\Models\Order::STATUSES as $key => $label)<section class="panel"><h2>{{ $label }}</h2><p class="stat">{{ $statusCounts[$key] ?? 0 }}</p></section>@endforeach</div>
<a class="button" href="{{ route('admin.orders') }}">Lihat pesanan simulasi</a>
@endsection