<?php

namespace App\Services;

use App\Models\Boat;
use App\Models\Booking;
use App\Models\BoatAvailability;
use Carbon\Carbon;

class CalendarService
{
    /**
     * Check if boat is available for a time period
     */
    public function isAvailable(Boat $boat, Carbon $startTime, Carbon $endTime): bool
    {
        // Check if boat is marked as available
        if (!$boat->is_available || !$boat->is_verified) {
            return false;
        }

        // Check for overlapping bookings (excluding cancelled)
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

        if ($overlapping) {
            return false;
        }

        // Check availability calendar
        $unavailableDates = BoatAvailability::where('boat_id', $boat->id)
            ->where('is_available', false)
            ->whereBetween('date', [$startTime->toDateString(), $endTime->toDateString()])
            ->exists();

        return !$unavailableDates;
    }

    /**
     * Get boat availability calendar
     */
    public function getAvailabilityCalendar(Boat $boat, Carbon $startDate, Carbon $endDate): array
    {
        $calendar = [];
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->toDateString();
            
            // Check if there's a specific availability entry
            $availability = BoatAvailability::where('boat_id', $boat->id)
                ->where('date', $dateStr)
                ->first();

            if ($availability) {
                $calendar[$dateStr] = [
                    'available' => $availability->is_available,
                    'reason' => $availability->reason,
                ];
            } else {
                // Check for bookings on this date
                $hasBooking = Booking::where('boat_id', $boat->id)
                    ->where('status', '!=', 'cancelled')
                    ->whereDate('start_time', '<=', $dateStr)
                    ->whereDate('end_time', '>=', $dateStr)
                    ->exists();

                $calendar[$dateStr] = [
                    'available' => !$hasBooking && $boat->is_available,
                    'reason' => $hasBooking ? 'Booked' : null,
                ];
            }

            $currentDate->addDay();
        }

        return $calendar;
    }

    /**
     * Block dates in calendar
     */
    public function blockDates(Boat $boat, array $dates, string $reason = null): void
    {
        foreach ($dates as $date) {
            BoatAvailability::updateOrCreate(
                [
                    'boat_id' => $boat->id,
                    'date' => $date,
                ],
                [
                    'is_available' => false,
                    'reason' => $reason,
                ]
            );
        }
    }

    /**
     * Unblock dates in calendar
     */
    public function unblockDates(Boat $boat, array $dates): void
    {
        BoatAvailability::where('boat_id', $boat->id)
            ->whereIn('date', $dates)
            ->delete();
    }
}

