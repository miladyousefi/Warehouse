<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RestaurantMenuSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'layout_type',
        'is_public',
        'share_token',
        'cover_image_path',
        'background_image_path',
    ];

    protected $appends = [
        'cover_image_url',
        'background_image_url',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image_path ? Storage::url($this->cover_image_path) : null;
    }

    public function getBackgroundImageUrlAttribute(): ?string
    {
        return $this->background_image_path ? Storage::url($this->background_image_path) : null;
    }
}
