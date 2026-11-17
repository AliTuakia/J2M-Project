<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductRatingController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_active, 404);

        $data = $request->validateWithBag('rating', [
            'score' => ['required', 'integer', 'between:1,5'],
        ], [
            'score.required' => 'Pilih rating 1 sampai 5 bintang.',
            'score.integer' => 'Rating harus berupa angka bulat.',
            'score.between' => 'Rating harus antara 1 dan 5 bintang.',
        ]);

        $product->ratings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['score' => $data['score']],
        );

        return redirect()->to(route('products.show', $product).'#rating')
            ->with('message', 'Rating Anda berhasil disimpan. Terima kasih!');
    }
}
