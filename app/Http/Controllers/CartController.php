<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Equipment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function show(Request $request)
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        return response()->json($cart->load(['items.equipment.vendor']));
    }

    public function addItem(Request $request)
    {
        $validated = $request->validate([
            'equipment_id' => 'required|exists:equipment,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $equipment = Equipment::findOrFail($validated['equipment_id']);

        // Check availability
        if (!$equipment->is_available || $equipment->quantity_available < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient quantity available'
            ], 400);
        }

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);

        // Check if item already in cart
        $cartItem = $cart->items()->where('equipment_id', $equipment->id)->first();

        if ($cartItem) {
            $newQuantity = $cartItem->quantity + $validated['quantity'];
            if ($equipment->quantity_available < $newQuantity) {
                return response()->json([
                    'message' => 'Insufficient quantity available'
                ], 400);
            }
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'equipment_id' => $equipment->id,
                'quantity' => $validated['quantity'],
                'price' => $equipment->daily_rate, // Use daily rate for purchase
            ]);
        }

        return response()->json($cart->load(['items.equipment.vendor']));
    }

    public function updateItem(Request $request, CartItem $cartItem)
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        if ($cartItem->cart->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($cartItem->equipment->quantity_available < $validated['quantity']) {
            return response()->json([
                'message' => 'Insufficient quantity available'
            ], 400);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);

        return response()->json($cartItem->cart->load(['items.equipment.vendor']));
    }

    public function removeItem(CartItem $cartItem)
    {
        if ($cartItem->cart->user_id !== request()->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cartItem->delete();

        return response()->json(['message' => 'Item removed from cart']);
    }

    public function clear(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->first();
        
        if ($cart) {
            $cart->items()->delete();
        }

        return response()->json(['message' => 'Cart cleared']);
    }
}

