<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentRental extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'equipment_id',
        'vendor_id',
        'quantity',
        'rental_start_date',
        'rental_end_date',
        'duration_days',
        'daily_rate',
        'subtotal',
        'tax',
        'service_fee',
        'total_amount',
        'status',
        'payment_status',
        'payment_intent_id',
        'delivery_address',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected $casts = [
        'rental_start_date' => 'date',
        'rental_end_date' => 'date',
        'cancelled_at' => 'datetime',
        'daily_rate' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    // Relationships
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function vendor()
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'rental_id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }
}

