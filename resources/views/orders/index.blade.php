@extends('layouts.app')
@section('title', $admin ? 'Kelola Pesanan' : 'Pesanan Saya')
@section('content')
<h1>{{ $admin ? 'Pesanan pelanggan' : 'Pesanan saya' }}</h1><p class="muted">Semua pesanan di website ini adalah simulasi.</p>
@forelse($orders as $order)<article class="panel order-row"><div><a class="text-link break-word" href="{{ route($admin ? 'admin.orders.show' : 'orders.show', $order) }}">{{ $order->number }}</a><p>{{ $order->created_at->format('d/m/Y H:i') }} UTC · {{ $order->recipient_name }}</p></div><div><span class="badge">{{ \App\Models\Order::STATUSES[$order->status] }}</span><p class="price">Rp{{ number_format($order->total, 0, ',', '.') }}</p></div></article>@empty<div class="empty-state"><h2>Belum ada pesanan</h2><p>Pesanan simulasi akan muncul setelah checkout berhasil.</p></div>@endforelse
@include('partials.pagination', ['paginator' => $orders])
@endsection