<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TripLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'boat_id',
        'captain_id',
        'customer_id',
        'trip_started_at',
        'trip_ended_at',
        'start_latitude',
        'start_longitude',
        'end_latitude',
        'end_longitude',
        'total_distance_nm',
        'max_speed_knots',
        'average_speed_knots',
        'status',
        'route_data',
        'safety_checkpoints',
    ];

    protected $casts = [
        'trip_started_at' => 'datetime',
        'trip_ended_at' => 'datetime',
        'start_latitude' => 'decimal:8',
        'start_longitude' => 'decimal:8',
        'end_latitude' => 'decimal:8',
        'end_longitude' => 'decimal:8',
        'total_distance_nm' => 'decimal:2',
        'max_speed_knots' => 'integer',
        'average_speed_knots' => 'integer',
        'route_data' => 'array',
        'safety_checkpoints' => 'array',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function boat()
    {
        return $this->belongsTo(Boat::class);
    }

    public function captain()
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function locations()
    {
        return $this->hasMany(TripLocation::class);
    }

    public function sosAlerts()
    {
        return $this->hasMany(SosAlert::class, 'booking_id', 'booking_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getDurationMinutes(): int
    {
        if (!$this->trip_started_at) {
            return 0;
        }

        $end = $this->trip_ended_at ?? now();
        return $this->trip_started_at->diffInMinutes($end);
    }
}

