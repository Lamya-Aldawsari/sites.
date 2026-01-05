<?php

namespace App\Services;

use App\Models\SplitPayment;
use App\Models\Booking;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;

class SplitPaymentService
{
    protected $paymentService;
    protected $platformFeePercentage = 15; // 15% platform fee

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Create split payment for a booking
     */
    public function createSplitPayment(Booking $booking): SplitPayment
    {
        $totalAmount = $booking->total_amount;
        $platformFee = $totalAmount * ($this->platformFeePercentage / 100);
        $captainAmount = $totalAmount - $platformFee;

        return SplitPayment::create([
            'paymentable_type' => Booking::class,
            'paymentable_id' => $booking->id,
            'total_amount' => $totalAmount,
            'platform_fee' => round($platformFee, 2),
            'captain_amount' => round($captainAmount, 2),
            'status' => 'pending',
        ]);
    }

    /**
     * Create split payment for an order (equipment)
     */
    public function createOrderSplitPayment(Order $order): SplitPayment
    {
        $totalAmount = $order->total_amount;
        $platformFee = $totalAmount * ($this->platformFeePercentage / 100);
        $vendorAmount = $totalAmount - $platformFee;

        return SplitPayment::create([
            'paymentable_type' => Order::class,
            'paymentable_id' => $order->id,
            'total_amount' => $totalAmount,
            'platform_fee' => round($platformFee, 2),
            'vendor_amount' => round($vendorAmount, 2),
            'status' => 'pending',
        ]);
    }

    /**
     * Process split payment transfers
     */
    public function processSplitPayment(SplitPayment $splitPayment): bool
    {
        DB::beginTransaction();
        try {
            $splitPayment->update(['status' => 'processing']);

            // Transfer to vendor/captain (Stripe Connect)
            if ($splitPayment->captain_amount) {
                $captain = $splitPayment->paymentable->captain;
                $transfer = $this->paymentService->createTransfer(
                    $captain->stripe_account_id ?? null,
                    $splitPayment->captain_amount
                );
                $splitPayment->update(['captain_transfer_id' => $transfer->id ?? null]);
            }

            if ($splitPayment->vendor_amount) {
                $vendor = $splitPayment->paymentable->items->first()->vendor ?? null;
                if ($vendor) {
                    $transfer = $this->paymentService->createTransfer(
                        $vendor->stripe_account_id ?? null,
                        $splitPayment->vendor_amount
                    );
                    $splitPayment->update(['vendor_transfer_id' => $transfer->id ?? null]);
                }
            }

            $splitPayment->update([
                'status' => 'completed',
                'processed_at' => now(),
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Failed to process split payment: ' . $e->getMessage());
            $splitPayment->update(['status' => 'failed']);
            return false;
        }
    }
}

