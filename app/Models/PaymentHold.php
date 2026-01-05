<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentHold extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'stripe_payment_intent_id',
        'amount',
        'status',
        'hold_expires_at',
        'captured_at',
        'released_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'hold_expires_at' => 'datetime',
        'captured_at' => 'datetime',
        'released_at' => 'datetime',
    ];

    // Relationships
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'held')
                     ->where(function ($q) {
                         $q->whereNull('hold_expires_at')
                           ->orWhere('hold_expires_at', '>', now());
                     });
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'held')
                     ->where('hold_expires_at', '<=', now());
    }
}

