<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\EquipmentRental;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EquipmentRentalController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->middleware('auth:sanctum');
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = EquipmentRental::with(['equipment', 'customer', 'vendor']);

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif ($user->isVendor()) {
            $query->where('vendor_id', $user->id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $rentals = $query->orderBy('rental_start_date', 'desc')->paginate(15);

        return response()->json($rentals);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'quantity' => 'required|integer|min:1',
            'rental_start_date' => 'required|date|after:today',
            'rental_end_date' => 'required|date|after:rental_start_date',
            'delivery_address' => 'nullable|string|max:500',
        ]);

        $equipment = Equipment::findOrFail($validated['equipment_id']);

        // Check availability
        if (!$equipment->is_available || $equipment->quantity_available < $validated['quantity']) {
            throw ValidationException::withMessages([
                'quantity' => ['Insufficient equipment available.'],
            ]);
        }

        // Calculate pricing
        $startDate = \Carbon\Carbon::parse($validated['rental_start_date']);
        $endDate = \Carbon\Carbon::parse($validated['rental_end_date']);
        $durationDays = $startDate->diffInDays($endDate) + 1;

        $dailyRate = $equipment->daily_rate;
        $subtotal = $dailyRate * $durationDays * $validated['quantity'];
        $tax = $subtotal * 0.10; // 10% tax
        $serviceFee = $subtotal * 0.05; // 5% service fee
        $total = $subtotal + $tax + $serviceFee;

        DB::beginTransaction();
        try {
            $rental = EquipmentRental::create([
                'customer_id' => $request->user()->id,
                'equipment_id' => $equipment->id,
                'vendor_id' => $equipment->vendor_id,
                'quantity' => $validated['quantity'],
                'rental_start_date' => $validated['rental_start_date'],
                'rental_end_date' => $validated['rental_end_date'],
                'duration_days' => $durationDays,
                'daily_rate' => $dailyRate,
                'subtotal' => round($subtotal, 2),
                'tax' => round($tax, 2),
                'service_fee' => round($serviceFee, 2),
                'total_amount' => round($total, 2),
                'delivery_address' => $validated['delivery_address'] ?? null,
                'status' => 'pending',
            ]);

            // Update equipment quantity
            $equipment->decrement('quantity_available', $validated['quantity']);

            // Create payment intent
            $paymentIntent = $this->paymentService->createPaymentIntent($rental->total_amount, [
                'rental_id' => $rental->id,
                'customer_id' => $request->user()->id,
            ]);

            $rental->update(['payment_intent_id' => $paymentIntent->id]);

            DB::commit();

            return response()->json([
                'rental' => $rental->load(['equipment', 'vendor']),
                'client_secret' => $paymentIntent->client_secret,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(EquipmentRental $equipmentRental)
    {
        $user = request()->user();

        if ($equipmentRental->customer_id !== $user->id && 
            $equipmentRental->vendor_id !== $user->id && 
            !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($equipmentRental->load(['equipment', 'customer', 'vendor', 'review']));
    }

    public function confirm(Request $request, EquipmentRental $equipmentRental)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        if ($equipmentRental->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payment = $this->paymentService->confirmPayment($validated['payment_intent_id']);

        if ($payment['status'] === 'succeeded') {
            $equipmentRental->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);

            $equipmentRental->transactions()->create([
                'user_id' => $request->user()->id,
                'type' => 'payment',
                'amount' => $equipmentRental->total_amount,
                'status' => 'completed',
                'stripe_payment_id' => $payment['id'],
            ]);

            return response()->json($equipmentRental->load(['equipment', 'vendor']));
        }

        return response()->json(['message' => 'Payment failed'], 400);
    }

    public function cancel(Request $request, EquipmentRental $equipmentRental)
    {
        $user = $request->user();

        if ($equipmentRental->customer_id !== $user->id && 
            $equipmentRental->vendor_id !== $user->id && 
            !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($equipmentRental->status, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'Cannot cancel rental in current status'], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $equipmentRental->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'] ?? null,
            ]);

            // Return equipment quantity
            $equipmentRental->equipment->increment('quantity_available', $equipmentRental->quantity);

            // Process refund if paid
            if ($equipmentRental->payment_status === 'paid' && $equipmentRental->payment_intent_id) {
                $refund = $this->paymentService->processRefund(
                    $equipmentRental->payment_intent_id, 
                    $equipmentRental->total_amount
                );
                
                if ($refund) {
                    $equipmentRental->update(['payment_status' => 'refunded']);
                    
                    $equipmentRental->transactions()->create([
                        'user_id' => $user->id,
                        'type' => 'refund',
                        'amount' => $equipmentRental->total_amount,
                        'status' => 'completed',
                        'stripe_refund_id' => $refund->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Rental cancelled successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}

