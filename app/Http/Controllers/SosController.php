<?php

namespace App\Http\Controllers;

use App\Models\SosAlert;
use App\Models\Booking;
use App\Services\SosService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SosController extends Controller
{
    protected $sosService;

    public function __construct(SosService $sosService)
    {
        $this->middleware('auth:sanctum');
        $this->sosService = $sosService;
    }

    public function createSos(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'message' => 'nullable|string|max:500',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);

        // Verify user is part of the booking
        $user = $request->user();
        if ($booking->customer_id !== $user->id && $booking->captain_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if booking is active
        if (!in_array($booking->status, ['confirmed', 'in_progress'])) {
            return response()->json(['message' => 'SOS can only be activated during active bookings'], 400);
        }

        $sosAlert = $this->sosService->createSosAlert(
            $booking,
            $user->id,
            $validated['latitude'],
            $validated['longitude'],
            $validated['message'] ?? null
        );

        return response()->json($sosAlert->load(['booking', 'user']), 201);
    }

    public function acknowledgeSos(Request $request, SosAlert $sosAlert)
    {
        // Only admins or emergency responders can acknowledge
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $sosAlert->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'responded_by' => $request->user()->id,
        ]);

        return response()->json($sosAlert->load('responder'));
    }

    public function resolveSos(Request $request, SosAlert $sosAlert)
    {
        $validated = $request->validate([
            'status' => 'required|in:resolved,false_alarm',
        ]);

        $sosAlert->update([
            'status' => $validated['status'],
            'resolved_at' => now(),
            'responded_by' => $request->user()->id,
        ]);

        return response()->json($sosAlert->load('responder'));
    }

    public function getActiveSos(Request $request)
    {
        $query = SosAlert::active()->with(['booking', 'user']);

        if ($request->user()->isAdmin()) {
            // Admins see all active SOS alerts
            $alerts = $query->get();
        } else {
            // Users see only their own alerts
            $alerts = $query->where('user_id', $request->user()->id)->get();
        }

        return response()->json($alerts);
    }
}

