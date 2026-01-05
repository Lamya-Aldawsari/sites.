<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Models\BoatLocation;
use App\Models\Booking;
use Illuminate\Http\Request;

class BoatLocationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function update(Request $request, Boat $boat)
    {
        $user = $request->user();

        // Only captain or admin can update location
        if ($boat->captain_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'speed' => 'nullable|numeric|min:0',
            'heading' => 'nullable|numeric|between:0,360',
            'booking_id' => 'nullable|exists:bookings,id',
        ]);

        // Get active booking if exists
        $activeBooking = Booking::where('boat_id', $boat->id)
            ->where('status', 'in_progress')
            ->first();

        $location = BoatLocation::create([
            'boat_id' => $boat->id,
            'booking_id' => $activeBooking?->id ?? $validated['booking_id'] ?? null,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
            'speed' => $validated['speed'] ?? null,
            'heading' => $validated['heading'] ?? null,
            'recorded_at' => now(),
        ]);

        // Update boat's current location
        $boat->update([
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        return response()->json($location);
    }

    public function show(Boat $boat, Request $request)
    {
        $query = $boat->locations()->orderBy('recorded_at', 'desc');

        if ($request->has('booking_id')) {
            $query->where('booking_id', $request->booking_id);
        }

        if ($request->has('limit')) {
            $query->limit($request->limit);
        } else {
            $query->limit(100);
        }

        $locations = $query->get();

        return response()->json($locations);
    }

    public function current(Boat $boat)
    {
        $location = $boat->locations()->latest('recorded_at')->first();

        if (!$location) {
            return response()->json([
                'latitude' => $boat->latitude,
                'longitude' => $boat->longitude,
            ]);
        }

        return response()->json($location);
    }
}

