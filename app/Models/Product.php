<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'unit_id',
        'name_tr',
        'name_en',
        'sku',
        'barcode',
        'description_tr',
        'description_en',
        'min_stock',
        'max_stock',
        'cost_price',
        'selling_price',
        'is_active',
        'track_quantity',
        'sort_order',
    ];

    protected $casts = [
        'min_stock' => 'decimal:4',
        'max_stock' => 'decimal:4',
        'cost_price' => 'decimal:4',
        'selling_price' => 'decimal:4',
        'is_active' => 'boolean',
        'track_quantity' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
