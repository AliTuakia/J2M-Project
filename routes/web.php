<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminProductController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/produk', [CatalogController::class, 'index'])->name('catalog');
Route::get('/produk/{product}', [CatalogController::class, 'show'])->name('products.show');
Route::get('/produk/{product}/masuk', [CatalogController::class, 'requireLogin'])->name('products.login');

Route::middleware('guest')->group(function () {
    Route::get('/masuk', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/masuk', [AuthController::class, 'login'])->middleware('throttle:20,1');
    Route::get('/daftar', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/daftar', [AuthController::class, 'register'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/keluar', [AuthController::class, 'logout'])->name('logout');
    Route::get('/keranjang', [CartController::class, 'index'])->name('cart.index');
    Route::post('/keranjang/{product}', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/keranjang/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/keranjang/{item}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/pesanan', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/pesanan/{order}', [OrderController::class, 'show'])->name('orders.show');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/produk', [AdminProductController::class, 'index'])->name('products');
    Route::get('/produk/tambah', [AdminProductController::class, 'create'])->name('products.create');
    Route::post('/produk', [AdminProductController::class, 'store'])->name('products.store');
    Route::get('/produk/{product}/ubah', [AdminProductController::class, 'edit'])->name('products.edit');
    Route::put('/produk/{product}', [AdminProductController::class, 'update'])->name('products.update');
    Route::delete('/produk/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
    Route::get('/kategori', [AdminController::class, 'categories'])->name('categories');
    Route::post('/kategori', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::put('/kategori/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
    Route::delete('/kategori/{category}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');
    Route::get('/pesanan', [AdminController::class, 'orders'])->name('orders');
    Route::get('/pesanan/{order}', [AdminController::class, 'order'])->name('orders.show');
    Route::patch('/pesanan/{order}', [AdminController::class, 'updateStatus'])->name('orders.update');
});
