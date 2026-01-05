<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Boat;
use App\Services\PaymentService;
use App\Services\BookingService;
use App\Services\PaymentHoldService;
use App\Services\SplitPaymentService;
use App\Services\CalendarService;
use App\Events\BookingCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    protected $paymentService;
    protected $bookingService;
    protected $paymentHoldService;
    protected $splitPaymentService;
    protected $calendarService;

    public function __construct(
        PaymentService $paymentService,
        BookingService $bookingService,
        PaymentHoldService $paymentHoldService,
        SplitPaymentService $splitPaymentService,
        CalendarService $calendarService
    ) {
        $this->middleware('auth:sanctum');
        $this->paymentService = $paymentService;
        $this->bookingService = $bookingService;
        $this->paymentHoldService = $paymentHoldService;
        $this->splitPaymentService = $splitPaymentService;
        $this->calendarService = $calendarService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Booking::with(['boat', 'customer', 'captain']);

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif ($user->isCaptain()) {
            $query->where('captain_id', $user->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderBy('start_time', 'desc')->paginate(15);

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'boat_id' => 'required|exists:boats,id',
            'booking_type' => 'required|in:hourly,daily,weekly',
            'booking_mode' => 'required|in:on_demand,scheduled',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'special_requests' => 'nullable|string',
            'pickup_location' => 'nullable|string',
            'dropoff_location' => 'nullable|string',
            'pickup_latitude' => 'nullable|numeric',
            'pickup_longitude' => 'nullable|numeric',
            'dropoff_latitude' => 'nullable|numeric',
            'dropoff_longitude' => 'nullable|numeric',
        ]);

        $boat = Boat::findOrFail($validated['boat_id']);

        // Check availability using calendar service
        $startTime = \Carbon\Carbon::parse($validated['start_time']);
        $endTime = \Carbon\Carbon::parse($validated['end_time']);
        
        if (!$this->calendarService->isAvailable($boat, $startTime, $endTime)) {
            throw ValidationException::withMessages([
                'start_time' => ['Boat is not available for the selected time period.'],
            ]);
        }

        // Calculate pricing
        $pricing = $this->bookingService->calculatePricing(
            $boat,
            $validated['booking_type'],
            $validated['start_time'],
            $validated['end_time']
        );

        DB::beginTransaction();
        try {
            $booking = Booking::create([
                'customer_id' => $request->user()->id,
                'boat_id' => $boat->id,
                'captain_id' => $boat->captain_id,
                'booking_type' => $validated['booking_type'],
                'booking_mode' => $validated['booking_mode'] ?? 'scheduled',
                'requires_captain' => true, // Always true - no self-drive allowed
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'duration' => $pricing['duration'],
                'subtotal' => $pricing['subtotal'],
                'tax' => $pricing['tax'],
                'service_fee' => $pricing['service_fee'],
                'total_amount' => $pricing['total'],
                'special_requests' => $validated['special_requests'] ?? null,
                'pickup_location' => $validated['pickup_location'] ?? null,
                'dropoff_location' => $validated['dropoff_location'] ?? null,
                'pickup_latitude' => $validated['pickup_latitude'] ?? null,
                'pickup_longitude' => $validated['pickup_longitude'] ?? null,
                'dropoff_latitude' => $validated['dropoff_latitude'] ?? null,
                'dropoff_longitude' => $validated['dropoff_longitude'] ?? null,
                'status' => 'pending',
            ]);

            // Create payment hold instead of immediate payment
            $paymentHold = $this->paymentHoldService->createHold($booking);
            $booking->update(['payment_intent_id' => $paymentHold->stripe_payment_intent_id]);
            
            // Create split payment record
            $this->splitPaymentService->createSplitPayment($booking);

            // Broadcast booking created event
            event(new BookingCreated($booking));

            DB::commit();

            return response()->json([
                'booking' => $booking->load(['boat', 'captain']),
                'client_secret' => $paymentIntent->client_secret,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Booking $booking)
    {
        $user = request()->user();

        if ($booking->customer_id !== $user->id && 
            $booking->captain_id !== $user->id && 
            !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($booking->load(['boat', 'customer', 'captain', 'review']));
    }

    public function confirm(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        if ($booking->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Verify payment
        $payment = $this->paymentService->confirmPayment($validated['payment_intent_id']);

        if ($payment['status'] === 'succeeded') {
            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
            ]);

            // Create transaction record
            $booking->transactions()->create([
                'user_id' => $request->user()->id,
                'type' => 'payment',
                'amount' => $booking->total_amount,
                'status' => 'completed',
                'stripe_payment_id' => $payment['id'],
            ]);

            return response()->json($booking->load(['boat', 'captain']));
        }

        return response()->json(['message' => 'Payment failed'], 400);
    }

    public function cancel(Request $request, Booking $booking)
    {
        $user = $request->user();

        if ($booking->customer_id !== $user->id && 
            $booking->captain_id !== $user->id && 
            !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($booking->status, ['pending', 'confirmed'])) {
            return response()->json(['message' => 'Cannot cancel booking in current status'], 400);
        }

        $validated = $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'] ?? null,
            ]);

            // Process refund if paid
            if ($booking->payment_status === 'paid' && $booking->payment_intent_id) {
                $refund = $this->paymentService->processRefund($booking->payment_intent_id, $booking->total_amount);
                
                if ($refund) {
                    $booking->update(['payment_status' => 'refunded']);
                    
                    $booking->transactions()->create([
                        'user_id' => $user->id,
                        'type' => 'refund',
                        'amount' => $booking->total_amount,
                        'status' => 'completed',
                        'stripe_refund_id' => $refund->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Booking cancelled successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function holdPayment(Request $request, Booking $booking)
    {
        if ($booking->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $hold = $this->paymentHoldService->createHold($booking);

        return response()->json([
            'hold' => $hold,
            'expires_at' => $hold->hold_expires_at,
        ]);
    }

    public function capturePayment(Request $request, Booking $booking)
    {
        $user = $request->user();

        if ($booking->customer_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $hold = $booking->paymentHold ?? null;
        if (!$hold || $hold->status !== 'held') {
            return response()->json(['message' => 'No active payment hold found'], 400);
        }

        if ($this->paymentHoldService->captureHold($hold)) {
            // Process split payment
            $splitPayment = $booking->splitPayments()->first();
            if ($splitPayment) {
                $this->splitPaymentService->processSplitPayment($splitPayment);
            }

            return response()->json(['message' => 'Payment captured successfully']);
        }

        return response()->json(['message' => 'Failed to capture payment'], 400);
    }
}

