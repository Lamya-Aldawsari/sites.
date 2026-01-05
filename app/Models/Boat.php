<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Boat extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'captain_id',
        'name',
        'slug',
        'description',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'type',
        'capacity',
        'length',
        'year',
        'make',
        'model',
        'hourly_rate',
        'daily_rate',
        'weekly_rate',
        'location',
        'latitude',
        'longitude',
        'amenities',
        'images',
        'is_available',
        'is_verified',
        'safety_certificate_number',
        'safety_certificate_expiry',
        'safety_certificate_verified',
        'verified_photos',
        'last_safety_inspection',
        'safety_rating',
        'rating',
        'total_reviews',
    ];

    protected $casts = [
        'amenities' => 'array',
        'images' => 'array',
        'meta_keywords' => 'array',
        'hourly_rate' => 'decimal:2',
        'daily_rate' => 'decimal:2',
        'weekly_rate' => 'decimal:2',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'is_available' => 'boolean',
        'is_verified' => 'boolean',
        'safety_certificate_expiry' => 'date',
        'last_safety_inspection' => 'date',
        'verified_photos' => 'array',
        'rating' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($boat) {
            if (empty($boat->slug)) {
                $boat->slug = \Illuminate\Support\Str::slug($boat->name);
            }
        });

        static::updating(function ($boat) {
            if ($boat->isDirty('name') && empty($boat->slug)) {
                $boat->slug = \Illuminate\Support\Str::slug($boat->name);
            }
        });
    }

    // Relationships
    public function captain()
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function availability()
    {
        return $this->hasMany(BoatAvailability::class);
    }

    public function locations()
    {
        return $this->hasMany(BoatLocation::class);
    }

    public function verificationDocuments()
    {
        return $this->hasMany(VerificationDocument::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true)
                     ->where('is_verified', true)
                     ->where('safety_certificate_verified', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true)
                     ->where('safety_certificate_verified', true)
                     ->whereHas('captain', function ($q) {
                         $q->where('license_verified', true)
                           ->where('is_verified', true);
                     });
    }

    public function scopeNearby($query, $latitude, $longitude, $radiusKm = 50)
    {
        return $query->selectRaw(
            '*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
            [$latitude, $longitude, $latitude]
        )
        ->having('distance', '<', $radiusKm)
        ->orderBy('distance');
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('location', 'like', "%{$term}%");
        });
    }
}

