<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminProductController extends Controller
{
    public function index()
    {
        return view('admin.products.index', ['products' => Product::with('category')->latest()->paginate(15)]);
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new Product(['is_active' => true]), 'categories' => Category::orderBy('name')->get()]);
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', ['product' => $product, 'categories' => Category::orderBy('name')->get()]);
    }

    private function productData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255', 'category_id' => 'required|integer|exists:categories,id',
            'description' => 'required|string|max:5000', 'price' => 'required|integer|min:0|max:1000000000',
            'stock' => 'required|integer|min:0|max:1000000',
            'is_active' => 'required|boolean', 'requires_custom' => 'required|boolean',
        ]);
    }

    public function store(Request $request)
    {
        Product::create($this->productData($request));

        return redirect()->route('admin.products')->with('message', 'Produk demo dibuat.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->productData($request);
        DB::transaction(function () use ($product, $data) {
            Product::whereKey($product->id)->lockForUpdate()->firstOrFail()->update($data);
        });

        return redirect()->route('admin.products')->with('message', 'Produk diperbarui. Riwayat pesanan tetap utuh.');
    }

    public function destroy(Product $product)
    {
        $product->update(['is_active' => false]);

        return back()->with('message', 'Produk dinonaktifkan dari katalog. Data historis tidak dihapus.');
    }
}
