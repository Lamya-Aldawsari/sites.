<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'boat_id',
        'captain_id',
        'booking_type',
        'booking_mode',
        'requires_captain',
        'estimated_arrival_minutes',
        'start_time',
        'end_time',
        'duration',
        'subtotal',
        'tax',
        'service_fee',
        'total_amount',
        'status',
        'payment_status',
        'payment_intent_id',
        'special_requests',
        'pickup_location',
        'dropoff_location',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_latitude',
        'dropoff_longitude',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cancelled_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'pickup_latitude' => 'decimal:8',
        'pickup_longitude' => 'decimal:8',
        'dropoff_latitude' => 'decimal:8',
        'dropoff_longitude' => 'decimal:8',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function boat()
    {
        return $this->belongsTo(Boat::class);
    }

    public function captain()
    {
        return $this->belongsTo(User::class, 'captain_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function paymentHold()
    {
        return $this->hasOne(PaymentHold::class);
    }

    public function splitPayments()
    {
        return $this->morphMany(SplitPayment::class, 'paymentable');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now());
    }

    public function scopeOnDemand($query)
    {
        return $query->where('booking_mode', 'on_demand');
    }

    public function scopeScheduled($query)
    {
        return $query->where('booking_mode', 'scheduled');
    }

    public function isOnDemand(): bool
    {
        return $this->booking_mode === 'on_demand';
    }

    public function isScheduled(): bool
    {
        return $this->booking_mode === 'scheduled';
    }
}

