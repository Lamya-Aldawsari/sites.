<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\Booking;
use App\Models\EquipmentRental;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'reviewable_type' => 'required|in:App\Models\Boat,App\Models\Equipment,App\Models\User',
            'reviewable_id' => 'required|integer',
            'booking_id' => 'nullable|exists:bookings,id',
            'rental_id' => 'nullable|exists:equipment_rentals,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        // Verify user can review (must have completed booking/rental)
        if ($validated['booking_id']) {
            $booking = Booking::findOrFail($validated['booking_id']);
            if ($booking->customer_id !== $user->id || $booking->status !== 'completed') {
                return response()->json(['message' => 'Cannot review this booking'], 403);
            }
        }

        if ($validated['rental_id']) {
            $rental = EquipmentRental::findOrFail($validated['rental_id']);
            if ($rental->customer_id !== $user->id || $rental->status !== 'completed') {
                return response()->json(['message' => 'Cannot review this rental'], 403);
            }
        }

        $review = Review::create([
            'user_id' => $user->id,
            'reviewable_type' => $validated['reviewable_type'],
            'reviewable_id' => $validated['reviewable_id'],
            'booking_id' => $validated['booking_id'] ?? null,
            'rental_id' => $validated['rental_id'] ?? null,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'is_verified' => true,
        ]);

        // Update rating on reviewable model
        $this->updateReviewableRating($validated['reviewable_type'], $validated['reviewable_id']);

        return response()->json($review->load('user'), 201);
    }

    protected function updateReviewableRating(string $type, int $id): void
    {
        $model = $type::find($id);
        if ($model) {
            $avgRating = Review::where('reviewable_type', $type)
                ->where('reviewable_id', $id)
                ->avg('rating');
            
            $totalReviews = Review::where('reviewable_type', $type)
                ->where('reviewable_id', $id)
                ->count();

            $model->update([
                'rating' => round($avgRating, 2),
                'total_reviews' => $totalReviews,
            ]);
        }
    }
}

