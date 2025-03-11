<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchase extends Model
{
    /** @use HasFactory<\Database\Factories\PurchaseFactory> */
    use HasFactory;
    protected $fillable = [
        'product_id',
        'purchase_price',
        'quantity',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class,'product_id');
    }
}
