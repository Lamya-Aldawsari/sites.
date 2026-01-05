<?php

namespace App\Services;

use App\Models\Boat;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Support\Collection;

class OnDemandBookingService
{
    protected $bookingService;
    protected $radiusKm = 10; // Default search radius for on-demand bookings

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /**
     * Find available boats near customer location for on-demand booking
     */
    public function findNearbyBoats(float $latitude, float $longitude, int $radiusKm = null): Collection
    {
        $radius = $radiusKm ?? $this->radiusKm;

        return Boat::available()
            ->nearby($latitude, $longitude, $radius)
            ->whereHas('captain', function ($query) {
                $query->where('is_active', true)
                      ->where('is_verified', true);
            })
            ->with(['captain', 'reviews'])
            ->get()
            ->map(function ($boat) use ($latitude, $longitude) {
                // Calculate estimated arrival time (rough estimate: 5 min per km)
                $distance = $this->calculateDistance(
                    $latitude,
                    $longitude,
                    $boat->latitude,
                    $boat->longitude
                );
                $boat->estimated_arrival_minutes = max(5, round($distance * 5)); // Minimum 5 minutes
                return $boat;
            })
            ->sortBy('estimated_arrival_minutes');
    }

    /**
     * Create an on-demand booking
     */
    public function createOnDemandBooking(
        User $customer,
        Boat $boat,
        float $pickupLatitude,
        float $pickupLongitude,
        ?float $dropoffLatitude = null,
        ?float $dropoffLongitude = null,
        int $durationMinutes = 60
    ): Booking {
        $startTime = now();
        $endTime = now()->addMinutes($durationMinutes);

        // Calculate pricing (on-demand uses hourly rate)
        $pricing = $this->bookingService->calculatePricing(
            $boat,
            'hourly',
            $startTime,
            $endTime
        );

        // Add on-demand surcharge (10% for immediate service)
        $onDemandSurcharge = $pricing['subtotal'] * 0.10;
        $pricing['subtotal'] += $onDemandSurcharge;
        $pricing['tax'] = $pricing['subtotal'] * 0.10;
        $pricing['service_fee'] = $pricing['subtotal'] * 0.05;
        $pricing['total'] = $pricing['subtotal'] + $pricing['tax'] + $pricing['service_fee'];

        // Calculate estimated arrival
        $distance = $this->calculateDistance(
            $pickupLatitude,
            $pickupLongitude,
            $boat->latitude,
            $boat->longitude
        );
        $estimatedArrival = max(5, round($distance * 5));

        return Booking::create([
            'customer_id' => $customer->id,
            'boat_id' => $boat->id,
            'captain_id' => $boat->captain_id,
            'booking_type' => 'hourly',
            'booking_mode' => 'on_demand',
            'requires_captain' => true, // Always true - no self-drive
            'estimated_arrival_minutes' => $estimatedArrival,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => ceil($durationMinutes / 60),
            'subtotal' => round($pricing['subtotal'], 2),
            'tax' => round($pricing['tax'], 2),
            'service_fee' => round($pricing['service_fee'], 2),
            'total_amount' => round($pricing['total'], 2),
            'pickup_latitude' => $pickupLatitude,
            'pickup_longitude' => $pickupLongitude,
            'dropoff_latitude' => $dropoffLatitude,
            'dropoff_longitude' => $dropoffLongitude,
            'status' => 'pending',
        ]);
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return $distance;
    }
}

