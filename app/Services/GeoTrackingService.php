<?php

namespace App\Services;

use App\Models\TripLog;
use App\Models\TripLocation;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class GeoTrackingService
{
    /**
     * Start tracking a trip
     */
    public function startTrip(Booking $booking): TripLog
    {
        $tripLog = TripLog::create([
            'booking_id' => $booking->id,
            'boat_id' => $booking->boat_id,
            'captain_id' => $booking->captain_id,
            'customer_id' => $booking->customer_id,
            'trip_started_at' => now(),
            'start_latitude' => $booking->pickup_latitude ?? $booking->boat->latitude,
            'start_longitude' => $booking->pickup_longitude ?? $booking->boat->longitude,
            'status' => 'active',
            'route_data' => [],
        ]);

        // Update booking status
        $booking->update(['status' => 'in_progress']);

        return $tripLog;
    }

    /**
     * Update trip location in real-time
     */
    public function updateLocation(
        TripLog $tripLog,
        float $latitude,
        float $longitude,
        ?float $speedKnots = null,
        ?float $headingDegrees = null,
        ?float $altitudeMeters = null,
        ?int $accuracyMeters = null
    ): TripLocation {
        // Calculate distance from start
        $distanceFromStart = $this->calculateDistance(
            $tripLog->start_latitude,
            $tripLog->start_longitude,
            $latitude,
            $longitude
        );

        $location = TripLocation::create([
            'trip_log_id' => $tripLog->id,
            'booking_id' => $tripLog->booking_id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'speed_knots' => $speedKnots,
            'heading_degrees' => $headingDegrees,
            'altitude_meters' => $altitudeMeters,
            'accuracy_meters' => $accuracyMeters,
            'distance_from_start_nm' => $distanceFromStart,
            'recorded_at' => now(),
        ]);

        // Update trip log route data (keep last 100 points for performance)
        $routeData = $tripLog->route_data ?? [];
        $routeData[] = [
            'lat' => $latitude,
            'lng' => $longitude,
            'speed' => $speedKnots,
            'heading' => $headingDegrees,
            'timestamp' => now()->toIso8601String(),
        ];

        // Keep only last 100 points
        if (count($routeData) > 100) {
            $routeData = array_slice($routeData, -100);
        }

        $tripLog->update([
            'route_data' => $routeData,
            'total_distance_nm' => $distanceFromStart,
            'max_speed_knots' => max($tripLog->max_speed_knots ?? 0, $speedKnots ?? 0),
        ]);

        // Update average speed
        $this->updateAverageSpeed($tripLog);

        // Broadcast location update via WebSocket
        broadcast(new \App\Events\TripLocationUpdated($location))->toOthers();

        // Cache current location for quick access
        Cache::put("trip:{$tripLog->id}:current_location", $location, 300);

        return $location;
    }

    /**
     * End trip tracking
     */
    public function endTrip(TripLog $tripLog, ?float $endLatitude = null, ?float $endLongitude = null): TripLog
    {
        $endLatitude = $endLatitude ?? $tripLog->boat->latitude;
        $endLongitude = $endLongitude ?? $tripLog->boat->longitude;

        // Get final location
        $finalLocation = $tripLog->locations()->latest('recorded_at')->first();

        $tripLog->update([
            'trip_ended_at' => now(),
            'end_latitude' => $endLatitude,
            'end_longitude' => $endLongitude,
            'status' => 'completed',
            'total_distance_nm' => $finalLocation ? $finalLocation->distance_from_start_nm : 0,
        ]);

        // Update booking status
        $tripLog->booking->update(['status' => 'completed']);

        // Calculate final statistics
        $this->calculateFinalStatistics($tripLog);

        return $tripLog;
    }

    /**
     * Get current location of active trip
     */
    public function getCurrentLocation(TripLog $tripLog): ?TripLocation
    {
        // Try cache first
        $cached = Cache::get("trip:{$tripLog->id}:current_location");
        if ($cached) {
            return $cached;
        }

        return $tripLog->locations()->latest('recorded_at')->first();
    }

    /**
     * Get trip route
     */
    public function getTripRoute(TripLog $tripLog, int $limit = 100): array
    {
        return $tripLog->locations()
            ->orderBy('recorded_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($location) {
                return [
                    'lat' => $location->latitude,
                    'lng' => $location->longitude,
                    'speed' => $location->speed_knots,
                    'heading' => $location->heading_degrees,
                    'timestamp' => $location->recorded_at->toIso8601String(),
                ];
            })
            ->toArray();
    }

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 3440; // Nautical miles

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        return round($distance, 2);
    }

    /**
     * Update average speed
     */
    protected function updateAverageSpeed(TripLog $tripLog): void
    {
        $locations = $tripLog->locations()->whereNotNull('speed_knots')->get();
        
        if ($locations->isEmpty()) {
            return;
        }

        $averageSpeed = $locations->avg('speed_knots');
        $tripLog->update(['average_speed_knots' => round($averageSpeed)]);
    }

    /**
     * Calculate final trip statistics
     */
    protected function calculateFinalStatistics(TripLog $tripLog): void
    {
        $locations = $tripLog->locations()->whereNotNull('speed_knots')->get();
        
        if ($locations->isEmpty()) {
            return;
        }

        $tripLog->update([
            'max_speed_knots' => $locations->max('speed_knots'),
            'average_speed_knots' => round($locations->avg('speed_knots')),
        ]);
    }
}

