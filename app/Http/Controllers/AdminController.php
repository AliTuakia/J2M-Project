<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    public function dashboard()
    {
        return view('admin.home', [
            'productCount' => Product::where('is_active', true)->count(),
            'orderCount' => Order::count(),
            'statusCounts' => Order::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function categories()
    {
        return view('admin.categories', ['categories' => Category::withCount('products')->orderBy('name')->get()]);
    }

    public function storeCategory(Request $request)
    {
        Category::create($request->validate(['name' => 'required|string|max:100|unique:categories,name']));

        return back()->with('message', 'Kategori dibuat.');
    }

    public function updateCategory(Request $request, Category $category)
    {
        $category->update($request->validate(['name' => ['required', 'string', 'max:100', Rule::unique('categories')->ignore($category->id)]]));

        return back()->with('message', 'Kategori diperbarui.');
    }

    public function destroyCategory(Category $category)
    {
        DB::transaction(function () use ($category) {
            $category = Category::whereKey($category->id)->lockForUpdate()->firstOrFail();
            if ($category->products()->exists()) {
                throw ValidationException::withMessages(['category' => 'Pindahkan semua produk dari kategori ini sebelum menghapusnya.']);
            }
            $category->delete();
        });

        return back()->with('message', 'Kategori dihapus.');
    }

    public function orders()
    {
        return view('orders.index', ['orders' => Order::latest()->paginate(15), 'admin' => true]);
    }

    public function order(Order $order)
    {
        return view('orders.show', ['order' => $order->load('items', 'user'), 'admin' => true]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate(['status' => ['required', Rule::in(['processing', 'completed'])]]);
        DB::transaction(function () use ($order, $data) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            $next = ['created' => 'processing', 'processing' => 'completed'];
            if (($next[$order->status] ?? null) !== $data['status']) {
                throw ValidationException::withMessages(['status' => 'Status hanya dapat maju satu tahap: Dibuat → Diproses → Selesai.']);
            }
            $order->update($data);
        });

        return back()->with('message', 'Status pesanan simulasi diperbarui.');
    }
}
