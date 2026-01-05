<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'trip_log_id',
        'booking_id',
        'latitude',
        'longitude',
        'speed_knots',
        'heading_degrees',
        'altitude_meters',
        'accuracy_meters',
        'distance_from_start_nm',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'speed_knots' => 'decimal:2',
        'heading_degrees' => 'decimal:2',
        'altitude_meters' => 'decimal:2',
        'accuracy_meters' => 'integer',
        'distance_from_start_nm' => 'decimal:2',
        'recorded_at' => 'datetime',
    ];

    // Relationships
    public function tripLog()
    {
        return $this->belongsTo(TripLog::class);
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}

