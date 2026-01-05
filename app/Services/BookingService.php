<?php

namespace App\Services;

use App\Models\Boat;
use App\Models\Booking;
use Carbon\Carbon;

class BookingService
{
    public function isAvailable(Boat $boat, $startTime, $endTime): bool
    {
        // Check if boat is marked as available
        if (!$boat->is_available || !$boat->is_verified) {
            return false;
        }

        // Check for overlapping bookings
        $overlapping = Booking::where('boat_id', $boat->id)
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->whereBetween('start_time', [$startTime, $endTime])
                      ->orWhereBetween('end_time', [$startTime, $endTime])
                      ->orWhere(function ($q) use ($startTime, $endTime) {
                          $q->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                      });
            })
            ->exists();

        return !$overlapping;
    }

    public function calculatePricing(Boat $boat, string $bookingType, $startTime, $endTime): array
    {
        $start = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);
        $duration = $start->diffInHours($end);

        $subtotal = 0;
        $rate = 0;

        switch ($bookingType) {
            case 'hourly':
                $rate = $boat->hourly_rate;
                $subtotal = $rate * $duration;
                break;
            case 'daily':
                $days = ceil($duration / 24);
                $rate = $boat->daily_rate;
                $subtotal = $rate * $days;
                break;
            case 'weekly':
                $weeks = ceil($duration / (24 * 7));
                $rate = $boat->weekly_rate ?? ($boat->daily_rate * 7);
                $subtotal = $rate * $weeks;
                break;
        }

        // Calculate tax (10% example)
        $tax = $subtotal * 0.10;

        // Service fee (5% example)
        $serviceFee = $subtotal * 0.05;

        $total = $subtotal + $tax + $serviceFee;

        return [
            'duration' => $duration,
            'rate' => $rate,
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'service_fee' => round($serviceFee, 2),
            'total' => round($total, 2),
        ];
    }
}

