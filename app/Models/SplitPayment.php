<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SplitPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'paymentable_type',
        'paymentable_id',
        'total_amount',
        'platform_fee',
        'vendor_amount',
        'captain_amount',
        'status',
        'platform_transfer_id',
        'vendor_transfer_id',
        'captain_transfer_id',
        'processed_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'vendor_amount' => 'decimal:2',
        'captain_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // Relationships
    public function paymentable()
    {
        return $this->morphTo();
    }
}

