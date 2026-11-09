<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cart = $request->user()->cart()->firstOrCreate();

        return view('page.cart', ['items' => $cart->items()->with('product')->get()]);
    }

    public function store(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);
        $data = $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
            'motorcycle_type' => ($product->requires_custom ? 'required' : 'nullable').'|string|max:100',
            'request_notes' => 'nullable|string|max:1000',
        ]);
        $cart = $request->user()->cart()->firstOrCreate();
        DB::transaction(function () use ($cart, $product, $data) {
            Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            abort_unless($product->is_active, 404);
            $inCart = $cart->items()->where('product_id', $product->id)->sum('quantity');
            if ($inCart + $data['quantity'] > $product->stock) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah seluruh item produk ini melebihi stok demo.']);
            }
            $attributes = [
                'product_id' => $product->id,
                'motorcycle_type' => $product->requires_custom ? $data['motorcycle_type'] : null,
                'request_notes' => $product->requires_custom ? ($data['request_notes'] ?? null) : null,
            ];
            $item = $cart->items()->firstOrNew($attributes);
            $quantity = ($item->quantity ?? 0) + $data['quantity'];
            if ($quantity > 99) {
                throw ValidationException::withMessages(['quantity' => 'Maksimal 99 untuk satu baris keranjang.']);
            }
            $item->quantity = $quantity;
            $item->save();
        });

        return redirect()->route('cart.index')->with('message', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request, int $item)
    {
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:99']);
        $cart = $request->user()->cart()->firstOrFail();
        DB::transaction(function () use ($cart, $item, $data) {
            Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $line = $cart->items()->findOrFail($item);
            $product = Product::whereKey($line->product_id)->lockForUpdate()->firstOrFail();
            $otherQuantity = $cart->items()->where('product_id', $product->id)->where('id', '!=', $line->id)->sum('quantity');
            if (! $product->is_active || $otherQuantity + $data['quantity'] > $product->stock) {
                throw ValidationException::withMessages(['quantity' => 'Produk tidak aktif atau stok demo tidak cukup.']);
            }
            $line->update($data);
        });

        return back()->with('message', 'Jumlah diperbarui.');
    }

    public function destroy(Request $request, int $item)
    {
        $cart = $request->user()->cart()->firstOrFail();
        DB::transaction(function () use ($cart, $item) {
            Cart::whereKey($cart->id)->lockForUpdate()->firstOrFail();
            $cart->items()->findOrFail($item)->delete();
        });

        return back()->with('message', 'Item dihapus dari keranjang.');
    }
}
