<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'category',
        'daily_rate',
        'weekly_rate',
        'quantity_available',
        'images',
        'location',
        'latitude',
        'longitude',
        'is_available',
        'rating',
        'total_reviews',
    ];

    protected $casts = [
        'images' => 'array',
        'meta_keywords' => 'array',
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_available' => 'boolean',
        'rating' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($equipment) {
            if (empty($equipment->slug)) {
                $equipment->slug = \Illuminate\Support\Str::slug($equipment->name);
            }
        });

        static::updating(function ($equipment) {
            if ($equipment->isDirty('name') && empty($equipment->slug)) {
                $equipment->slug = \Illuminate\Support\Str::slug($equipment->name);
            }
        });
    }

    // Relationships
    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function rentals()
    {
        return $this->hasMany(EquipmentRental::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
                     ->where('quantity_available', '>', 0);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
}

