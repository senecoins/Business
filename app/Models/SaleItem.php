<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    /** @use HasFactory<\Database\Factories\SaleItemFactory> */
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'sale_items';
    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_value',
        'final_price',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected static function booted()
    {
        static::saving(function ($item) {
            if (is_null($item->final_price)) {
                $item->calculateFinalPrice();
            }
        });
    }

    public function calculateFinalPrice()
    {
        $subtotal = $this->unit_price * $this->quantity;
        $this->final_price = max(0, $subtotal - $this->discount_value);
    }
}
