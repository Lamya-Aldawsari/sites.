<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'zip_code',
        'profile_image',
        'bio',
        'is_verified',
        'is_active',
        'verification_documents',
        'years_experience',
        'license_number',
        'license_expiry_date',
        'license_verified',
        'certifications',
        'emergency_contacts',
        'captain_rating',
        'captain_total_reviews',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'verification_documents' => 'array',
        'license_expiry_date' => 'date',
        'certifications' => 'array',
        'emergency_contacts' => 'array',
        'is_verified' => 'boolean',
        'is_active' => 'boolean',
        'license_verified' => 'boolean',
        'captain_rating' => 'decimal:2',
    ];

    // Relationships
    public function boats()
    {
        return $this->hasMany(Boat::class, 'captain_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function captainBookings()
    {
        return $this->hasMany(Booking::class, 'captain_id');
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class, 'vendor_id');
    }

    public function equipmentRentals()
    {
        return $this->hasMany(EquipmentRental::class, 'customer_id');
    }

    public function vendorRentals()
    {
        return $this->hasMany(EquipmentRental::class, 'vendor_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function verificationDocuments()
    {
        return $this->hasMany(VerificationDocument::class);
    }

    // Helper methods
    public function isCaptain(): bool
    {
        return $this->role === 'captain' || $this->role === 'owner';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner' || $this->role === 'captain';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }

    public function isVendor(): bool
    {
        return $this->role === 'vendor';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}

