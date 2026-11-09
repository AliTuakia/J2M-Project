<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['product_id', 'product_name', 'unit_price', 'quantity', 'subtotal', 'motorcycle_type', 'request_notes'];
}
