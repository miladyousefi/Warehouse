<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_tr',
        'name_en',
        'symbol',
        'base_unit_id',
        'ratio_to_base',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'ratio_to_base' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    public function baseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'base_unit_id');
    }

    public function subUnits(): HasMany
    {
        return $this->hasMany(Unit::class, 'base_unit_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id');
    }
}
