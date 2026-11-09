<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = ['product_id', 'quantity', 'motorcycle_type', 'request_notes'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
