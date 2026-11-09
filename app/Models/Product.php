<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'stock',
        'is_active',
        'requires_custom',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'stock' => 'integer',
            'is_active' => 'boolean',
            'requires_custom' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function imagePath(): ?string
    {
        // Pemetaan foto yang dikonfirmasi pemilik; tidak mengubah data produk.
        $images = [
            'busa' => 'busa.jpeg',
            'fiber / tulangan jok' => 'fiber.jpeg',
            'kulit jok biasa' => 'kulit polos.jpeg',
            'kulit jok motif' => 'kulit motif.jpeg',
            'latex' => 'latex.jpeg',
            'satu set jok custom' => 'kulit motif(1).jpeg',
        ];

        $filename = $images[strtolower(trim($this->name ?? ''))] ?? null;
        if (! $filename) {
            return null;
        }

        $path = 'images/products/'.$filename;

        return is_file(public_path($path)) ? $path : null;
    }

    public function usesIllustration(): bool
    {
        return strtolower(trim($this->name ?? '')) === 'satu set jok custom';
    }
}
