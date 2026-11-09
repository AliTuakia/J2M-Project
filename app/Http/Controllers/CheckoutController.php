<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function create(Request $request)
    {
        $cart = $request->user()->cart()->firstOrCreate();
        $items = $cart->items()->with('product')->orderBy('id')->get();
        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('message', 'Tambahkan produk terlebih dahulu.');
        }
        $token = (string) Str::uuid();
        $request->session()->put('checkout_review', [
            'token' => $token,
            'items' => $items->map(fn ($item) => [
                'id' => $item->id, 'product_id' => $item->product_id, 'quantity' => $item->quantity,
                'price' => $item->product?->price, 'motorcycle_type' => $item->motorcycle_type,
                'request_notes' => $item->request_notes,
            ])->all(),
        ]);

        return view('page.checkout', compact('items', 'token'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'checkout_token' => 'required|uuid', 'recipient_name' => 'required|string|max:100',
            'address' => 'required|string|min:10|max:1000',
            'shipping_method' => 'required|in:simulation', 'payment_method' => 'required|in:simulation',
            'simulation_agreement' => 'accepted',
        ]);
        if ($order = $request->user()->orders()->where('checkout_token', $data['checkout_token'])->first()) {
            return redirect()->route('orders.show', $order);
        }
        $review = $request->session()->get('checkout_review');
        if (! $review || $review['token'] !== $data['checkout_token']) {
            return redirect()->route('checkout.create')->withErrors(['checkout' => 'Sesi checkout berubah. Tinjau ulang pesanan.']);
        }
        $order = $this->placeOrder($request->user(), $data, $review['items']);
        $request->session()->forget('checkout_review');

        return redirect()->route('orders.show', $order)->with('message', 'Pesanan simulasi berhasil dibuat. Tidak ada pembayaran atau pengiriman nyata.');
    }

    private function placeOrder(User $user, array $data, array $review): Order
    {
        return DB::transaction(function () use ($user, $data, $review) {
            // All cart writes share this lock; a repeated token returns the original order.
            $cart = Cart::where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            if ($existing = $user->orders()->where('checkout_token', $data['checkout_token'])->first()) {
                return $existing;
            }
            $items = $cart->items()->orderBy('id')->get();
            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Keranjang kosong.']);
            }
            $products = Product::whereIn('id', $items->pluck('product_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $currentReview = $items->map(fn ($item) => [
                'id' => $item->id, 'product_id' => $item->product_id, 'quantity' => $item->quantity,
                'price' => $products->get($item->product_id)?->price,
                'motorcycle_type' => $item->motorcycle_type, 'request_notes' => $item->request_notes,
            ])->all();
            if ($currentReview !== $review) {
                throw ValidationException::withMessages(['cart' => 'Harga atau keranjang berubah. Tinjau kembali checkout sebelum mengonfirmasi.']);
            }
            foreach ($items->groupBy('product_id') as $id => $group) {
                $product = $products->get($id);
                if (! $product || ! $product->is_active || $group->sum('quantity') > $product->stock) {
                    throw ValidationException::withMessages(['stock' => 'Produk tidak tersedia atau stok demo tidak cukup. Perbarui keranjang.']);
                }
                foreach ($group as $item) {
                    if ($item->quantity < 1 || $item->quantity > 99 || ($product->requires_custom && ! $item->motorcycle_type)) {
                        throw ValidationException::withMessages(['cart' => 'Data item tidak valid. Hapus lalu tambahkan kembali produk.']);
                    }
                }
            }
            $total = $items->sum(fn ($item) => $products[$item->product_id]->price * $item->quantity);
            $order = $user->orders()->create([
                'checkout_token' => $data['checkout_token'], 'number' => 'J2M-'.Str::upper((string) Str::ulid()),
                'recipient_name' => $data['recipient_name'], 'address' => $data['address'],
                'shipping_method' => 'simulation', 'payment_method' => 'simulation', 'shipping_cost' => 0,
                'total' => $total, 'status' => 'created',
            ]);
            foreach ($items as $item) {
                $product = $products[$item->product_id];
                $order->items()->create([
                    'product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => $product->price,
                    'quantity' => $item->quantity, 'subtotal' => $product->price * $item->quantity,
                    'motorcycle_type' => $item->motorcycle_type, 'request_notes' => $item->request_notes,
                ]);
                $product->decrement('stock', $item->quantity);
            }
            $cart->items()->delete();

            return $order;
        }, 3);
    }
}
