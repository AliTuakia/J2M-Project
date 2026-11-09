@extends('layouts.app')
@section('title', 'Checkout Simulasi')
@section('content')
<h1>Checkout simulasi</h1><div class="notice">Tidak ada uang yang ditagih atau barang yang dikirim. Gunakan data penerima dan alamat fiktif.</div>
<div class="checkout-grid"><form method="post" action="{{ route('checkout.store') }}" class="panel stack">@csrf<input type="hidden" name="checkout_token" value="{{ $token }}">
<div><label for="recipient_name">Nama penerima</label><input id="recipient_name" name="recipient_name" maxlength="100" value="{{ old('recipient_name', auth()->user()->name) }}" required></div>
<div><label for="address">Alamat penerima</label><textarea id="address" name="address" minlength="10" maxlength="1000" rows="4" required>{{ old('address') }}</textarea></div>
<div><label for="shipping_method">Pengiriman</label><select id="shipping_method" name="shipping_method"><option value="simulation">Pengiriman simulasi — Rp0 (bukan layanan kurir)</option></select></div>
<div><label for="payment_method">Pembayaran</label><select id="payment_method" name="payment_method"><option value="simulation">Pembayaran simulasi — tanpa uang sungguhan</option></select></div>
<label class="checkbox"><input type="checkbox" name="simulation_agreement" value="1" required @checked(old('simulation_agreement'))>Saya memahami bahwa pesanan ini hanya simulasi.</label>
<button type="submit">Buat pesanan simulasi</button><a href="{{ route('cart.index') }}">Kembali ke keranjang</a></form>
<aside class="panel"><h2>Ringkasan pesanan</h2>@foreach($items as $item)<div class="order-line"><strong>{{ $item->product->name }}</strong><p>{{ $item->quantity }} × Rp{{ number_format($item->product->price, 0, ',', '.') }}</p>@if($item->motorcycle_type)<p class="preserve-text">{{ $item->motorcycle_type }} · {{ $item->request_notes }}</p>@endif</div>@endforeach<p>Pengiriman simulasi: Rp0</p><p class="price">Total Rp{{ number_format($items->sum(fn ($item) => $item->quantity * $item->product->price), 0, ',', '.') }}</p><small>Harga dihitung ulang di server. Jika berubah, Anda harus meninjau ulang sebelum memesan.</small></aside></div>
@endsection