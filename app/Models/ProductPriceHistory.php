<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPriceHistory extends Model
{
    protected $fillable = [
        'product_id',
        'price',
        'previous_price',
        'new_price',
        'reason',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'previous_price' => 'decimal:2',
        'new_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
