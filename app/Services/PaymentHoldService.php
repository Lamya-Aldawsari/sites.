<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\PaymentHold;
use App\Services\PaymentService;
use Carbon\Carbon;

class PaymentHoldService
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create a payment hold for a booking
     */
    public function createHold(Booking $booking, int $holdDays = 7): PaymentHold
    {
        // Create payment intent with capture_method = manual
        $paymentIntent = $this->paymentService->createPaymentIntent(
            $booking->total_amount,
            [
                'booking_id' => $booking->id,
                'customer_id' => $booking->customer_id,
            ],
            ['capture_method' => 'manual']
        );

        $hold = PaymentHold::create([
            'booking_id' => $booking->id,
            'stripe_payment_intent_id' => $paymentIntent->id,
            'amount' => $booking->total_amount,
            'status' => 'held',
            'hold_expires_at' => now()->addDays($holdDays),
        ]);

        return $hold;
    }

    /**
     * Capture held payment
     */
    public function captureHold(PaymentHold $hold): bool
    {
        try {
            $this->paymentService->capturePaymentIntent($hold->stripe_payment_intent_id);

            $hold->update([
                'status' => 'captured',
                'captured_at' => now(),
            ]);

            // Update booking payment status
            $hold->booking->update(['payment_status' => 'paid']);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to capture payment hold: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Release held payment
     */
    public function releaseHold(PaymentHold $hold): bool
    {
        try {
            $this->paymentService->cancelPaymentIntent($hold->stripe_payment_intent_id);

            $hold->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

            // Update booking status
            $hold->booking->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded',
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to release payment hold: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check and expire old holds
     */
    public function expireOldHolds(): void
    {
        PaymentHold::expired()->get()->each(function ($hold) {
            $this->releaseHold($hold);
            $hold->update(['status' => 'expired']);
        });
    }
}

