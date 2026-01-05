<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BoatAvailability extends Model
{
    use HasFactory;

    protected $table = 'boat_availability';

    protected $fillable = [
        'boat_id',
        'date',
        'start_time',
        'end_time',
        'is_available',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_available' => 'boolean',
    ];

    // Relationships
    public function boat()
    {
        return $this->belongsTo(Boat::class);
    }
}

