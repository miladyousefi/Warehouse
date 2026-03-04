<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_table_id',
        'order_code',
        'status',
        'payment_status',
        'subtotal',
        'customer_note',
        'cancel_reason',
        'source',
        'placed_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'placed_at' => 'datetime',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RestaurantOrderItem::class)->with('menuItem');
    }
}
