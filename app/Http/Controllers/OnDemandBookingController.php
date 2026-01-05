<?php

namespace App\Http\Controllers;

use App\Models\Boat;
use App\Services\OnDemandBookingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnDemandBookingController extends Controller
{
    protected $onDemandService;
    protected $paymentService;

    public function __construct(OnDemandBookingService $onDemandService, PaymentService $paymentService)
    {
        $this->middleware('auth:sanctum');
        $this->onDemandService = $onDemandService;
        $this->paymentService = $paymentService;
    }

    /**
     * Find nearby boats for on-demand booking
     */
    public function findNearbyBoats(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'nullable|integer|min:1|max:50',
        ]);

        $boats = $this->onDemandService->findNearbyBoats(
            $validated['latitude'],
            $validated['longitude'],
            $validated['radius_km'] ?? null
        );

        return response()->json([
            'boats' => $boats,
            'count' => $boats->count(),
        ]);
    }

    /**
     * Create an on-demand booking
     */
    public function create(Request $request)
    {
        $validated = $request->validate([
            'boat_id' => 'required|exists:boats,id',
            'pickup_latitude' => 'required|numeric|between:-90,90',
            'pickup_longitude' => 'required|numeric|between:-180,180',
            'dropoff_latitude' => 'nullable|numeric|between:-90,90',
            'dropoff_longitude' => 'nullable|numeric|between:-180,180',
            'duration_minutes' => 'nullable|integer|min:30|max:480', // 30 min to 8 hours
            'pickup_location' => 'nullable|string|max:255',
            'dropoff_location' => 'nullable|string|max:255',
        ]);

        $boat = Boat::findOrFail($validated['boat_id']);

        // Verify boat is available for on-demand
        if (!$boat->is_available || !$boat->is_verified) {
            throw ValidationException::withMessages([
                'boat_id' => ['This boat is not available for on-demand bookings.'],
            ]);
        }

        // Verify captain is available
        if (!$boat->captain->is_active || !$boat->captain->is_verified) {
            throw ValidationException::withMessages([
                'boat_id' => ['Captain is not available at this time.'],
            ]);
        }

        DB::beginTransaction();
        try {
            $booking = $this->onDemandService->createOnDemandBooking(
                $request->user(),
                $boat,
                $validated['pickup_latitude'],
                $validated['pickup_longitude'],
                $validated['dropoff_latitude'] ?? null,
                $validated['dropoff_longitude'] ?? null,
                $validated['duration_minutes'] ?? 60
            );

            // Update pickup/dropoff locations if provided
            if (isset($validated['pickup_location'])) {
                $booking->update(['pickup_location' => $validated['pickup_location']]);
            }
            if (isset($validated['dropoff_location'])) {
                $booking->update(['dropoff_location' => $validated['dropoff_location']]);
            }

            // Create payment intent
            $paymentIntent = $this->paymentService->createPaymentIntent($booking->total_amount, [
                'booking_id' => $booking->id,
                'customer_id' => $request->user()->id,
                'booking_mode' => 'on_demand',
            ]);

            $booking->update(['payment_intent_id' => $paymentIntent->id]);

            DB::commit();

            return response()->json([
                'booking' => $booking->load(['boat', 'captain']),
                'client_secret' => $paymentIntent->client_secret,
                'estimated_arrival_minutes' => $booking->estimated_arrival_minutes,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

