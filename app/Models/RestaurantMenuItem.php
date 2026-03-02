<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class RestaurantMenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_menu_category_id',
        'name_tr',
        'name_en',
        'description_tr',
        'description_en',
        'image_path',
        'image_gallery_paths',
        'sale_price',
        'is_active',
        'sort_order',
    ];

    protected $appends = [
        'image_url',
        'image_gallery_urls',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
        'image_gallery_paths' => 'array',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::url($this->image_path) : null;
    }

    public function getImageGalleryUrlsAttribute(): array
    {
        $paths = $this->image_gallery_paths ?? [];
        return collect($paths)
            ->filter()
            ->map(fn ($path) => Storage::url($path))
            ->values()
            ->all();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RestaurantMenuCategory::class, 'restaurant_menu_category_id');
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(RestaurantMenuItemIngredient::class)->with('product.unit');
    }
}
