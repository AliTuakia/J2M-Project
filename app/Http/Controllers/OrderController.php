<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return view('orders.index', ['orders' => $request->user()->orders()->latest()->paginate(10), 'admin' => false]);
    }

    public function show(Request $request, int $order)
    {
        return view('orders.show', ['order' => $request->user()->orders()->with('items')->findOrFail($order), 'admin' => false]);
    }
}
