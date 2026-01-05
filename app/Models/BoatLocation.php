<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoatLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'boat_id',
        'booking_id',
        'latitude',
        'longitude',
        'speed',
        'heading',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'speed' => 'decimal:2',
        'heading' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    // Relationships
    public function boat()
    {
        return $this->belongsTo(Boat::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}

