<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\TripLog;
use App\Services\GeoTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripTrackingController extends Controller
{
    protected $geoTrackingService;

    public function __construct(GeoTrackingService $geoTrackingService)
    {
        $this->middleware('auth:sanctum');
        $this->geoTrackingService = $geoTrackingService;
    }

    public function startTrip(Request $request, Booking $booking)
    {
        $user = $request->user();

        // Only captain can start trip
        if ($booking->captain_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'confirmed') {
            return response()->json(['message' => 'Booking must be confirmed to start trip'], 400);
        }

        // Check if trip already started
        $existingTrip = TripLog::where('booking_id', $booking->id)->where('status', 'active')->first();
        if ($existingTrip) {
            return response()->json(['message' => 'Trip already started', 'trip_log' => $existingTrip], 400);
        }

        $tripLog = $this->geoTrackingService->startTrip($booking);

        return response()->json($tripLog->load(['boat', 'captain', 'customer']), 201);
    }

    public function updateLocation(Request $request, TripLog $tripLog)
    {
        $user = $request->user();

        // Only captain can update location
        if ($tripLog->captain_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($tripLog->status !== 'active') {
            return response()->json(['message' => 'Trip is not active'], 400);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed_knots' => 'nullable|numeric|min:0|max:100',
            'heading_degrees' => 'nullable|numeric|between:0,360',
            'altitude_meters' => 'nullable|numeric',
            'accuracy_meters' => 'nullable|integer|min:0',
        ]);

        $location = $this->geoTrackingService->updateLocation(
            $tripLog,
            $validated['latitude'],
            $validated['longitude'],
            $validated['speed_knots'] ?? null,
            $validated['heading_degrees'] ?? null,
            $validated['altitude_meters'] ?? null,
            $validated['accuracy_meters'] ?? null
        );

        return response()->json($location);
    }

    public function endTrip(Request $request, TripLog $tripLog)
    {
        $user = $request->user();

        // Only captain can end trip
        if ($tripLog->captain_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'end_latitude' => 'nullable|numeric|between:-90,90',
            'end_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $tripLog = $this->geoTrackingService->endTrip(
            $tripLog,
            $validated['end_latitude'] ?? null,
            $validated['end_longitude'] ?? null
        );

        return response()->json($tripLog->load(['boat', 'captain', 'customer']));
    }

    public function getCurrentLocation(TripLog $tripLog)
    {
        $location = $this->geoTrackingService->getCurrentLocation($tripLog);

        if (!$location) {
            return response()->json(['message' => 'No location data available'], 404);
        }

        return response()->json($location);
    }

    public function getTripRoute(TripLog $tripLog, Request $request)
    {
        $limit = $request->get('limit', 100);
        $route = $this->geoTrackingService->getTripRoute($tripLog, $limit);

        return response()->json([
            'trip_log_id' => $tripLog->id,
            'route' => $route,
            'total_points' => count($route),
        ]);
    }

    public function getActiveTrips(Request $request)
    {
        $user = $request->user();
        $query = TripLog::active()->with(['boat', 'captain', 'customer']);

        if ($user->isCaptain() || $user->isOwner()) {
            $query->where('captain_id', $user->id);
        } elseif ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        }

        $trips = $query->get()->map(function ($trip) {
            $currentLocation = $this->geoTrackingService->getCurrentLocation($trip);
            return [
                'trip_log' => $trip,
                'current_location' => $currentLocation,
            ];
        });

        return response()->json($trips);
    }
}

