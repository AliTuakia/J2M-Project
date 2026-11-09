<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = ['created' => 'Dibuat', 'processing' => 'Diproses', 'completed' => 'Selesai'];

    protected $fillable = ['checkout_token', 'number', 'recipient_name', 'address', 'shipping_method', 'payment_method', 'shipping_cost', 'total', 'status'];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
