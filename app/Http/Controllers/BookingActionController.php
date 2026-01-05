<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingActionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function accept(Request $request, Booking $booking)
    {
        $user = $request->user();

        if ($booking->captain_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking is not in pending status'], 400);
        }

        $booking->update(['status' => 'confirmed']);

        // Broadcast notification to customer
        broadcast(new \App\Events\BookingAccepted($booking))->toOthers();

        return response()->json($booking->load(['customer', 'boat']));
    }

    public function reject(Request $request, Booking $booking)
    {
        $user = $request->user();

        if ($booking->captain_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking is not in pending status'], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'] ?? 'Rejected by captain',
            ]);

            // Release payment hold
            if ($booking->paymentHold) {
                app(\App\Services\PaymentHoldService::class)->releaseHold($booking->paymentHold);
            }

            // Broadcast notification to customer
            broadcast(new \App\Events\BookingRejected($booking))->toOthers();

            DB::commit();

            return response()->json(['message' => 'Booking rejected successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

