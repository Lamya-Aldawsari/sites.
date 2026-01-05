<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
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
        $query = Order::with(['items.equipment', 'items.vendor']);

        if ($user->isCustomer()) {
            $query->where('customer_id', $user->id);
        } elseif ($user->isVendor()) {
            $query->whereHas('items', function ($q) use ($user) {
                $q->where('vendor_id', $user->id);
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'required|string|max:255',
            'shipping_state' => 'required|string|max:255',
            'shipping_country' => 'required|string|max:255',
            'shipping_zip' => 'required|string|max:20',
        ]);

        $cart = Cart::where('user_id', $request->user()->id)
            ->with('items.equipment')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // Validate all items are still available
        foreach ($cart->items as $item) {
            if (!$item->equipment->is_available || 
                $item->equipment->quantity_available < $item->quantity) {
                return response()->json([
                    'message' => "{$item->equipment->name} is no longer available in requested quantity"
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            // Calculate totals
            $subtotal = $cart->items->sum(function ($item) {
                return $item->quantity * $item->price;
            });
            $tax = $subtotal * 0.10; // 10% tax
            $shipping = 25.00; // Fixed shipping cost (can be made dynamic)
            $total = $subtotal + $tax + $shipping;

            // Create order
            $order = Order::create([
                'customer_id' => $request->user()->id,
                'subtotal' => round($subtotal, 2),
                'tax' => round($tax, 2),
                'shipping' => $shipping,
                'total_amount' => round($total, 2),
                'shipping_address' => $validated['shipping_address'],
                'shipping_city' => $validated['shipping_city'],
                'shipping_state' => $validated['shipping_state'],
                'shipping_country' => $validated['shipping_country'],
                'shipping_zip' => $validated['shipping_zip'],
                'status' => 'pending',
            ]);

            // Create order items and update equipment quantities
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'equipment_id' => $cartItem->equipment_id,
                    'vendor_id' => $cartItem->equipment->vendor_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'subtotal' => $cartItem->quantity * $cartItem->price,
                ]);

                // Decrease equipment quantity
                $cartItem->equipment->decrement('quantity_available', $cartItem->quantity);
            }

            // Create payment intent
            $paymentIntent = $this->paymentService->createPaymentIntent($order->total_amount, [
                'order_id' => $order->id,
                'customer_id' => $request->user()->id,
            ]);

            $order->update(['payment_intent_id' => $paymentIntent->id]);

            // Clear cart
            $cart->items()->delete();

            DB::commit();

            return response()->json([
                'order' => $order->load(['items.equipment', 'items.vendor']),
                'client_secret' => $paymentIntent->client_secret,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Order $order)
    {
        $user = request()->user();

        if ($order->customer_id !== $user->id && 
            !$order->items()->where('vendor_id', $user->id)->exists() &&
            !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($order->load(['items.equipment', 'items.vendor', 'customer']));
    }

    public function confirm(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        if ($order->customer_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $payment = $this->paymentService->confirmPayment($validated['payment_intent_id']);

        if ($payment['status'] === 'succeeded') {
            $order->update([
                'status' => 'processing',
                'payment_status' => 'paid',
            ]);

            $order->transactions()->create([
                'user_id' => $request->user()->id,
                'type' => 'payment',
                'amount' => $order->total_amount,
                'status' => 'completed',
                'stripe_payment_id' => $payment['id'],
            ]);

            return response()->json($order->load(['items.equipment', 'items.vendor']));
        }

        return response()->json(['message' => 'Payment failed'], 400);
    }
}

