<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();

        if (!$user->isVendor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $equipmentCount = Equipment::where('vendor_id', $user->id)->count();
        $availableEquipment = Equipment::where('vendor_id', $user->id)
            ->where('is_available', true)
            ->sum('quantity_available');

        $totalOrders = OrderItem::where('vendor_id', $user->id)
            ->distinct('order_id')
            ->count('order_id');

        $pendingOrders = OrderItem::where('vendor_id', $user->id)
            ->whereHas('order', function ($query) {
                $query->where('status', 'processing');
            })
            ->distinct('order_id')
            ->count('order_id');

        $totalRevenue = OrderItem::where('vendor_id', $user->id)
            ->whereHas('order', function ($query) {
                $query->where('payment_status', 'paid');
            })
            ->sum('subtotal');

        $recentOrders = Order::whereHas('items', function ($query) use ($user) {
            $query->where('vendor_id', $user->id);
        })
        ->with(['items' => function ($query) use ($user) {
            $query->where('vendor_id', $user->id)->with('equipment');
        }, 'customer'])
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

        return response()->json([
            'stats' => [
                'equipment_count' => $equipmentCount,
                'available_quantity' => $availableEquipment,
                'total_orders' => $totalOrders,
                'pending_orders' => $pendingOrders,
                'total_revenue' => round($totalRevenue, 2),
            ],
            'recent_orders' => $recentOrders,
        ]);
    }

    public function inventory(Request $request)
    {
        $user = $request->user();

        if (!$user->isVendor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Equipment::where('vendor_id', $user->id)
            ->with('reviews');

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        if ($request->has('available_only')) {
            $query->where('is_available', true)
                  ->where('quantity_available', '>', 0);
        }

        $equipment = $query->orderBy('created_at', 'desc')->paginate(20);

        return response()->json($equipment);
    }

    public function updateInventory(Request $request, Equipment $equipment)
    {
        $user = $request->user();

        if ($equipment->vendor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'quantity_available' => 'sometimes|integer|min:0',
            'is_available' => 'sometimes|boolean',
            'daily_rate' => 'sometimes|numeric|min:0',
        ]);

        $equipment->update($validated);

        return response()->json($equipment);
    }

    public function orders(Request $request)
    {
        $user = $request->user();

        if (!$user->isVendor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = Order::whereHas('items', function ($q) use ($user) {
            $q->where('vendor_id', $user->id);
        })
        ->with(['items' => function ($query) use ($user) {
            $query->where('vendor_id', $user->id)->with('equipment');
        }, 'customer']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($orders);
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        $user = $request->user();

        // Check if order has items from this vendor
        $vendorItems = $order->items()->where('vendor_id', $user->id)->exists();
        
        if (!$vendorItems) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|in:processing,shipped,delivered',
            'tracking_number' => 'nullable|string|max:255',
        ]);

        // Update order status
        $order->update([
            'status' => $validated['status'],
            'tracking_number' => $validated['tracking_number'] ?? $order->tracking_number,
        ]);

        return response()->json($order->load(['items.equipment', 'customer']));
    }
}

