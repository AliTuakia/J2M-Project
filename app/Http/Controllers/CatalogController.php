<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function home()
    {
        return view('page.home', [
            'products' => Product::with('category')
                ->where('is_active', true)
                ->limit(6)
                ->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function index(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|max:100',
            'category' => 'nullable|integer|exists:categories,id',
        ]);

        $products = Product::with('category')
            ->where('is_active', true)
            ->when($data['q'] ?? null, fn ($query, $q) => $query->where('name', 'like', '%'.$q.'%'))
            ->when($data['category'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        return view('page.catalog', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function show(Product $product)
    {
        abort_unless($product->is_active, 404);

        // Legacy seed descriptions are not verified product specifications.
        $description = in_array($product->description, [
            'Harga untuk kebutuhan satu jok motor.',
            'Deskripsi lengkap dan satuan produk belum tersedia.',
            'Harga tetap untuk demo. Isi tipe motor dan catatan permintaan Anda.',
        ], true) ? null : $product->description;

        if ($product->usesIllustration()) {
            $description = 'Jok motor custom yang dapat disesuaikan dengan selera Anda. Pelanggan dapat menentukan kombinasi bahan, warna, motif, tekstur, dan model jahitan sesuai keinginan.';
        }

        return view('page.product-detail', compact('product', 'description'));
    }

    public function requireLogin(Request $request, Product $product)
    {
        abort_unless($product->is_active, 404);

        if ($request->user()) {
            return redirect()->route('products.show', $product);
        }

        $request->session()->put('url.intended', route('products.show', $product));

        return redirect()->route('login')->with('message', 'Silakan masuk atau daftar, lalu tambahkan produk dari halaman detail.');
    }
}
